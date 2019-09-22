<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This is the L10n main interface class, used by the components. It
 * allows you to get entries from the l10n string tables in the current
 * language with an automatic conversion to the destination character
 * set.
 *
 * <b>L10n language database file format specification:</b>
 *
 * Lines starting with --- are considered command lines and treated specially,
 * unless they occur within string data. All commands are separated with at
 * least a single space from their content, unless they don't have an argument.
 *
 * Empty lines are ignored, unless within string data.
 *
 * All keys and values will be trim'ed when encountered, so leading and trailing
 * whitespace will be eliminated completely.
 *
 * Windows-style line endings (\r\n) will be silently converted to the UNIX
 * \n style.
 *
 * Commented example:
 *
 * <pre>
 * ---# Lines starting with a # command are ignored.
 *
 * ---# File format version
 * ---VERSION 2.1.0
 *
 * ---# Language of the table
 * ---LANGUAGE en
 *
 * ---STRING string identifier
 * TRANSLATED STRING taken literally until ---STRINGEND, which is the
 * only reserved value at the beginning of the line, everything else is
 * fine. Linebreaks within the translation are preserved.
 * \r\n sequences are translated into to \n
 * ---STRINGEND
 * </pre>
 *
 * File naming scheme: {$component_directory}/locale/{$database_name}.{$lang}.txt
 *
 * @package midcom.services
 */
class midcom_services_i18n_l10n
{
    /**
     * The name of the locale library we use, this is usually
     * a component's name.
     *
     * @var string
     */
    private $_library;

    /**
     * The full path basename to the active library files. The individual
     * files are ending with .$lang.txt.
     *
     * @var string
     */
    private $_library_filename;

    /**
     * The name of the current component
     *
     * @var string
     */
    private $_component_name;

    /**
     * Fallback language, in case the selected language is not available.
     *
     * @var string
     */
    private $_fallback_language;

    /**
     * Current language.
     *
     * @var string
     */
    private $_language;

    /**
     * Global string table cache, it stores the string tables
     * loaded during runtime.
     *
     * @var Array
     */
    private static $_localedb = [];

    /**
     * The string database, a reference into the global cache.
     *
     * @var Array
     */
    private $_stringdb;

    /**
     * The current L10n DB file format number
     *
     * @var string
     */
    private $_version = '2.1.0';

    private $database;

    /**
     * The constructor loads the translation library indicated by the snippetdir
     * path $library and initializes the system completely. The output character
     * set will be initialized to the language's default.
     *
     * @param string $library    Name of the locale library to use.
     * @param string $database    Name of the database in the library to load.
     */
    public function __construct($library, $database)
    {
        $path = midcom::get()->componentloader->path_to_snippetpath($library) . "/locale/" . $database;
        $this->database = $database;
        $this->_library_filename = $path;
        $this->_library = $library . $database;
        $this->_component_name = $library;

        $this->_fallback_language = midcom::get()->i18n->get_fallback_language();

        if (!isset(self::$_localedb[$this->_library])) {
            self::$_localedb[$this->_library] = [];
        }

        $this->_stringdb =& self::$_localedb[$this->_library];

        $this->set_language(midcom::get()->i18n->get_current_language());
    }

    /**
     * Load a language database
     *
     * - Leading and trailing whitespace will be eliminated
     */
    private function _load_language(string $lang)
    {
        $this->_stringdb[$lang] = [];
        $filename = "{$this->_library_filename}.{$lang}.txt";

        if (midcom::get()->config->get('cache_module_memcache_backend') != 'flatfile') {
            $stringtable = midcom::get()->cache->memcache->get('L10N', $filename);
            if (is_array($stringtable)) {
                $this->_stringdb[$lang] = $stringtable;
                return;
            }
        }

        if (!empty(midcom::get()->componentloader->manifests[$this->_component_name]->extends)) {
            $parent_l10n = new self(midcom::get()->componentloader->manifests[$this->_component_name]->extends, $this->database);
            $this->_stringdb[$lang] = $parent_l10n->get_stringdb($lang);
        }

        if (!file_exists($filename)) {
            return;
        }

        $data = $this->parse_data(file($filename), $lang, $filename);

        // get site-specific l10n
        $component_locale = midcom_helper_misc::get_snippet_content_graceful("conf:/" . $this->_component_name . '/l10n/'. $this->database . '.' . $lang . '.txt');
        if (!empty($component_locale)) {
            $data = array_merge($data, $this->parse_data(explode("\n", $component_locale), $lang, $component_locale));
        }

        $this->_stringdb[$lang] = array_merge($this->_stringdb[$lang], $data);

        if (midcom::get()->config->get('cache_module_memcache_backend') != 'flatfile') {
            midcom::get()->cache->memcache->put('L10N', $filename, $this->_stringdb[$lang]);
        }
    }

    private function parse_data(array $data, string $lang, string $filename) : array
    {
        $stringtable = [];
        $version = '';
        $language = '';
        $instring = false;
        $string_data = '';
        $string_key = '';

        foreach ($data as $line => $string) {
            // Kill any excess whitespace first.
            $string = trim($string);

            if (!$instring) {
                // outside of a string value
                if ($string == '') {
                    continue;
                }
                if (substr($string, 0, 3) != '---') {
                    throw $this->error("Invalid line", $filename, $line);
                }
                // this is a command
                if (strlen($string) < 4) {
                    throw $this->error("An incorrect command was detected", $filename, $line);
                }

                $command = preg_replace('/^---(.+?) .+/', '$1', $string);

                switch ($command) {
                    case '#':
                        // Skip
                        break;

                    case 'VERSION':
                        if ($version != '') {
                            throw $this->error("A second VERSION tag has been detected", $filename, $line);
                        }
                        $version = substr($string, 11);
                        break;

                    case 'LANGUAGE':
                        if ($language != '') {
                            throw $this->error("A second LANGUAGE tag has been detected", $filename, $line);
                        }
                        $language = substr($string, 12);
                        break;

                    case 'STRING':
                        $string_data = '';
                        $string_key = substr($string, 10);
                        $instring = true;
                        break;

                    default:
                        throw $this->error("Unknown command '{$command}'", $filename, $line);
                }
            } elseif ($string == '---STRINGEND') {
                $instring = false;
                $stringtable[$string_key] = $string_data;
            } elseif ($string_data == '') {
                $string_data .= $string;
            } else {
                $string_data .= "\n{$string}";
            }
        }

        if ($instring) {
            throw new midcom_error("L10n DB SYNTAX ERROR: String constant exceeds end of file.");
        }
        if (version_compare($version, $this->_version, "<")) {
            throw new midcom_error("L10n DB ERROR: File format version of {$filename} is too old, no update available at the moment.");
        }
        if ($lang != $language) {
            throw new midcom_error("L10n DB ERROR: The DB language version {$language} did not match the requested {$lang}.");
        }

        ksort($stringtable, SORT_STRING);
        return $stringtable;
    }

    private function error(string $message, string $filename, int $line) : midcom_error
    {
        $line++; // Array is 0-indexed
        return new midcom_error('L10n DB SYNTAX ERROR: ' .  $message . ' at ' . $filename . ' ' . $line);
    }

    /**
     * Checks, whether the referenced language is already loaded. If not,
     * it is automatically made available.
     *
     * @param string $lang The language to check for.
     * @see midcom_services_i18n_l10n::_load_language()
     */
    private function _check_for_language(string $lang)
    {
        if (!array_key_exists($lang, $this->_stringdb)) {
            $this->_load_language($lang);
        }
    }

    /**
     * Set output language.
     *
     * This is usually set through midcom_services_i18n.
     *
     * @param string $lang    Language code.
     * @see midcom_services_i18n::set_language()
     */
    public function set_language($lang)
    {
        $this->_language = $lang;
    }

    /**
     * Set the fallback language.
     *
     * This is usually set through midcom_services_i18n.
     *
     * @param string $lang    Language name.
     * @see midcom_services_i18n::set_fallback_language()
     */
    public function set_fallback_language($lang)
    {
        $this->_fallback_language = $lang;
    }

    public function get_formatter() : midcom_services_i18n_formatter
    {
        return new midcom_services_i18n_formatter($this->_language);
    }

    /**
     * Checks if a localized string for $string exists. If $language is unset,
     * the current language is used.
     *
     * @param string $string The string-ID to search for.
     * @param string $language The language to search in.
     */
    function string_exists($string, $language = null) : bool
    {
        if ($language === null) {
            $language = $this->_language;
        }

        $this->_check_for_language($language);

        return isset($this->_stringdb[$language][$string]);
    }

    /**
     * Checks whether the given string is available in either the current
     * or the fallback language. Use this to determine if an actually processed
     * result is returned by get. This is helpful especially if you want to
     * "catch" cases where a string might translate to itself in some languages.
     *
     * @param string $string The string-ID to search for
     */
    function string_available($string)
    {
        return
        (
               $this->string_exists($string, $this->_language)
            || $this->string_exists($string, $this->_fallback_language)
        );
    }

    /**
     * Retrieves a localized string from the database using $language as
     * destination. If $language is unset, the currently set default language is
     * used. If the string is not found in the selected language, the fallback
     * is checked. If even the fallback cannot be found, then $string is
     * returned and the event is logged to MidCOMs Debugging system.
     *
     * L10n DB loads are done through string_exists.
     *
     * @param string $string The string-ID to search for.
     * @param string $language The language to search in, uses the current language as default.
     */
    public function get($string, $language = null) : string
    {
        if ($language === null) {
            $language = $this->_language;
        }

        if (!$this->string_exists($string, $language)) {
            // Go for Fallback
            $language = $this->_fallback_language;

            if (!$this->string_exists($string, $language)) {
                // Nothing found, log is produced by string_exists.
                return $string;
            }
        }

        return midcom::get()->i18n->convert_from_utf8($this->_stringdb[$language][$string]);
    }

    /**
     * This is a shortcut for "echo $this->get(...);", useful in style code.
     *
     * Note, that due to the stupidity of the Zend engine, it is not possible to call
     * this function echo, like it should have been called.
     *
     * @param string $string The string-ID to search for.
     * @param string $language The language to search in, uses the current language as default.
     * @see get()
     */
    public function show($string, $language = null)
    {
        echo $this->get($string, $language);
    }

    /**
     * Returns the entire translation table for the given language
     *
     * @param string $language The language to query
     */
    public function get_stringdb($language) : array
    {
        $this->_check_for_language($language);
        if (empty($this->_stringdb[$language])) {
            return [];
        }
        return $this->_stringdb[$language];
    }
}
