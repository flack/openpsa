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
 * \r\n sequences are translated into \n
 * ---STRINGEND
 * </pre>
 *
 * File naming scheme: {$component_directory}/locale/default.{$lang}.txt
 *
 * @package midcom.services
 */
class midcom_services_i18n_l10n
{
    /**
     * The name of the locale library we use, this is usually
     * a component's name.
     */
    private string $_library;

    /**
     * The full path basename to the active library files. The individual
     * files are ending with .$lang.txt.
     */
    private string $_library_filename;

    /**
     * Fallback language, in case the selected language is not available.
     */
    private string $_fallback_language;

    /**
     * Current language.
     */
    private string $_language;

    /**
     * The string database
     */
    private array $_stringdb = [];

    /**
     * The current L10n DB file format number
     */
    private string $_version = '2.1.0';

    /**
     * The constructor loads the translation library indicated by the snippetdir
     * path $component and initializes the system completely. The output character
     * set will be initialized to the language's default.
     */
    public function __construct(string $component, string $language, string $fallback_language)
    {
        $this->_library = $component;
        $this->_fallback_language = $fallback_language;
        $this->_language = $language;
        $this->_library_filename = midcom::get()->componentloader->path_to_snippetpath($component) . "/locale/default";
    }

    /**
     * Load a language database
     *
     * - Leading and trailing whitespace will be eliminated
     */
    private function _load_language(string $lang) : array
    {
        $filename = "{$this->_library_filename}.{$lang}.txt";
        $identifier = str_replace('/', '-', $filename);

        if (midcom::get()->config->get('cache_module_memcache_backend') != 'flatfile') {
            $stringtable = midcom::get()->cache->memcache->get('L10N', $identifier);
            if (is_array($stringtable)) {
                return $stringtable;
            }
        }

        if (!file_exists($filename)) {
            return [];
        }

        $data = $this->parse_data(file($filename), $lang, $filename);

        // get site-specific l10n
        $snippet_path = "conf:/" . $this->_library . '/l10n/default.' . $lang . '.txt';
        if ($snippet_data = midcom_helper_misc::get_snippet_content_graceful($snippet_path)) {
            $data = array_merge($data, $this->parse_data(explode("\n", $snippet_data), $lang, $snippet_path));
        }

        if (midcom::get()->config->get('cache_module_memcache_backend') != 'flatfile') {
            midcom::get()->cache->memcache->put('L10N', $identifier, $data);
        }
        return $data;
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
                if (!str_starts_with($string, '---')) {
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
     */
    private function _check_for_language(string $lang)
    {
        if (!array_key_exists($lang, $this->_stringdb)) {
            $this->_stringdb[$lang] = $this->_load_language($lang);
        }
    }

    /**
     * Set output language.
     *
     * This is usually set through midcom_services_i18n.
     */
    public function set_language(string $lang)
    {
        $this->_language = $lang;
    }

    /**
     * Set the fallback language.
     *
     * This is usually set through midcom_services_i18n.
     */
    public function set_fallback_language(string $lang)
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
     * @param string $language The language to search in.
     */
    function string_exists(string $string, string $language = null) : bool
    {
        $language ??= $this->_language;
        $this->_check_for_language($language);

        return isset($this->_stringdb[$language][$string]);
    }

    /**
     * Checks whether the given string is available in either the current
     * or the fallback language. Use this to determine if an actually processed
     * result is returned by get. This is helpful especially if you want to
     * "catch" cases where a string might translate to itself in some languages.
     */
    function string_available(string $string)
    {
        return $this->string_exists($string, $this->_language)
            || $this->string_exists($string, $this->_fallback_language);
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
     * @param string $language The language to search in, uses the current language as default.
     */
    public function get(string $string, $language = null) : string
    {
        $language ??= $this->_language;

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
     * @param string $language The language to search in, uses the current language as default.
     * @see get()
     */
    public function show(string $string, $language = null)
    {
        echo $this->get($string, $language);
    }
}
