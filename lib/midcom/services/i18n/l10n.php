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
     * A copy of the language DB from i18n.
     *
     * @var Array
     */
    private $_language_db;

    /**
     * Fallback language, in case the selected language is not available.
     *
     * @var string
     */
    private $_fallback_language;

    /**
     * Current character set
     *
     * @var string
     */
    private $_charset;

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
    private static $_localedb = array();

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

        $this->_language_db = midcom::get()->i18n->get_language_db();
        $this->_fallback_language = midcom::get()->i18n->get_fallback_language();

        if (!isset(self::$_localedb[$this->_library]))
        {
            self::$_localedb[$this->_library] = array();
        }

        $this->_stringdb =& self::$_localedb[$this->_library];

        $this->set_language(midcom::get()->i18n->get_current_language());
        $this->set_charset(midcom::get()->i18n->get_current_charset());
    }

    /**
     * This will flush the complete string table to the filesystem.
     * No locking code is in place, so check that there are no concurrent
     * accesses to the file have to be done on a social level.
     *
     * It will write all loaded languages to disk, regardless of changes.
     */
    function flush()
    {
        foreach ($this->_stringdb as $lang => $table)
        {
            $file = fopen("{$this->_library_filename}.{$lang}.txt", 'w');
            if (!$file)
            {
                midcom::get()->uimessages->add("L10N Error", "Failed to open the file '{$this->_library_filename}.{$lang}.txt' for writing.", 'error');
                debug_add("Failed to open the file '{$this->_library_filename}.{$lang}.txt' for writing.", MIDCOM_LOG_ERROR);
                return false;
            }

            fwrite($file, "---# MidCOM String Database\n");
            fwrite($file, "---VERSION 2.1.0\n");
            fwrite($file, "---LANGUAGE {$lang}\n\n");

            foreach ($table as $key => $translation)
            {
                $key = trim($key);
                $translation = str_replace("\r\n", "\n", trim($translation));
                fwrite($file, "---STRING {$key}\n");
                fwrite($file, "{$translation}\n");
                fwrite($file, "---STRINGEND\n\n");
            }

            fclose($file);
        }
    }

    /**
     * Load a language database
     *
     * - Leading and trailing whitespace will be eliminated
     */
    private function _load_language($lang)
    {
        $this->_stringdb[$lang] = array();
        $filename = "{$this->_library_filename}.{$lang}.txt";

        if (midcom::get()->config->get('cache_module_memcache_backend') != 'flatfile')
        {
            $stringtable = midcom::get()->cache->memcache->get('L10N', $filename);
            if (is_array($stringtable))
            {
                $this->_stringdb[$lang] = $stringtable;
                return;
            }
        }

        if (!empty(midcom::get()->componentloader->manifests[$this->_component_name]->extends))
        {
            $parent_l10n = new self(midcom::get()->componentloader->manifests[$this->_component_name]->extends, $this->database);
            $this->_stringdb[$lang] = $parent_l10n->get_stringdb($lang);
        }

        if (!file_exists($filename))
        {
            return;
        }

        $data = $this->parse_data(file($filename), $lang, $filename);

        // get site-specific l10n
        $component_locale = midcom_helper_misc::get_snippet_content_graceful("conf:/" . $this->_component_name . '/l10n/'. $this->database . '.' . $lang . '.txt');
        if (!empty($component_locale))
        {
            $data = array_merge($data, $this->parse_data(explode("\n", $component_locale), $lang, $component_locale));
        }

        $this->_stringdb[$lang] = array_merge($this->_stringdb[$lang], $data);

        if (midcom::get()->config->get('cache_module_memcache_backend') != 'flatfile')
        {
            midcom::get()->cache->memcache->put('L10N', $filename, $this->_stringdb[$lang]);
        }
    }

    private function parse_data(array $data, $lang, $filename)
    {
        $stringtable = array();
        $version = '';
        $language = '';
        $instring = false;
        $string_data = '';
        $string_key = '';

        foreach ($data as $line => $string)
        {
            // Kill any excess whitespace first.
            $string = trim($string);

            if (! $instring)
            {
                // outside of a string value
                if ($string == '')
                {
                    continue;
                }
                if (substr($string, 0, 3) == '---')
                {
                    // this is a command
                    if (strlen($string) < 4)
                    {
                        $line++; // Array is 0-indexed
                        throw new midcom_error("L10n DB SYNTAX ERROR: An incorrect command was detected at {$filename}:{$line}");
                    }

                    $command = preg_replace('/^---(.+?) .+/', '$1', $string);

                    switch ($command)
                    {
                        case '#':
                            // Skip
                            break;

                        case 'VERSION':
                            if ($version != '')
                            {
                                $line++; // Array is 0-indexed
                                throw new midcom_error("L10n DB SYNTAX ERROR: A second VERSION tag has been detected at {$filename}:{$line}");
                            }
                            $version = substr($string, 11);
                            break;

                        case 'LANGUAGE':
                            if ($language != '')
                            {
                                $line++; // Array is 0-indexed
                                throw new midcom_error("L10n DB SYNTAX ERROR: A second LANGUAGE tag has been detected at {$filename}:{$line}");
                            }
                            $language = substr($string, 12);
                            break;

                        case 'STRING':
                            $string_data = '';
                            $string_key = substr($string, 10);
                            $instring = true;
                            break;

                        default:
                            $line++; // Array is 0-indexed
                            throw new midcom_error("L10n DB SYNTAX ERROR: Unknown command '{$command}' at {$filename}:{$line}");
                    }
                }
                else
                {
                    $line++; // Array is 0-indexed
                    throw new midcom_error("L10n DB SYNTAX ERROR: Invalid line at {$filename}:{$line}");
                }
            }
            else
            {
                // Within a string value
                if ($string == '---STRINGEND')
                {
                    $instring = false;
                    $stringtable[$string_key] = $string_data;
                }
                else
                {
                    if ($string_data == '')
                    {
                        $string_data .= $string;
                    }
                    else
                    {
                        $string_data .= "\n{$string}";
                    }
                }
            }
        }

        if ($instring)
        {
            throw new midcom_error("L10n DB SYNTAX ERROR: String constant exceeds end of file.");
        }
        if (version_compare($version, $this->_version, "<"))
        {
            throw new midcom_error("L10n DB ERROR: File format version of {$filename} is too old, no update available at the moment.");
        }
        if ($lang != $language)
        {
            throw new midcom_error("L10n DB ERROR: The DB language version {$language} did not match the requested {$lang}.");
        }

        ksort($stringtable, SORT_STRING);
        return $stringtable;
    }

    /**
     * Checks, whether the referenced language is already loaded. If not,
     * it is automatically made available.
     *
     * @param string $lang The language to check for.
     * @see midcom_services_i18n_l10n::_load_language()
     */
    private function _check_for_language($lang)
    {
        if (! array_key_exists($lang, $this->_stringdb))
        {
            $this->_load_language($lang);
        }
    }

    /**
     * This tries to load the language files for all languages defined
     * in the i18n's language database.
     */
    private function _load_all_languages()
    {
        foreach (array_keys($this->_language_db) as $lang)
        {
            $this->_check_for_language($lang);
        }
    }

    /**
     * Set output character set.
     *
     * This is usually set through midcom_services_i18n.
     *
     * @param string $charset    Charset name.
     * @see midcom_services_i18n::set_charset()
     */
    function set_charset ($charset)
    {
        $this->_charset = strtolower($charset);
    }

    /**
     * Set output language.
     *
     * This will set the character encoding to the language's default
     * encoding and will also set the system locale to the one
     * specified in the language database.
     *
     * If you want another character encoding as the default one, you
     * have to override it manually using midcom_services_i18n_l10n::set_charset()
     * after calling this method.
     *
     * This is usually set through midcom_services_i18n.
     *
     * @param string $lang    Language code.
     * @see midcom_services_i18n::set_language()
     */
    function set_language($lang)
    {
        if (!array_key_exists($lang, $this->_language_db))
        {
            debug_add("Language {$lang} not found in the language database.", MIDCOM_LOG_ERROR);
            return false;
        }

        $this->_language = $lang;
        $this->_charset = $this->_language_db[$lang]["encoding"];
    }

    /**
     * Set the fallback language.
     *
     * This is usually set through midcom_services_i18n.
     *
     * @param string $lang    Language name.
     * @see midcom_services_i18n::set_fallback_language()
     */
    function set_fallback_language ($lang)
    {
        $this->_fallback_language = $lang;
    }

    /**
     * @return midcom_services_i18n_formatter
     */
    public function get_formatter()
    {
        return new midcom_services_i18n_formatter($this->_language);
    }

    /**
     * Checks if a localized string for $string exists. If $language is unset,
     * the current language is used.
     *
     * @param string $string The string-ID to search for.
     * @param string $language The language to search in.
     * @return boolean Indicating availability.
     */
    function string_exists($string, $language = null)
    {
        if (is_null($language))
        {
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
     * @return boolean Indicating availability.
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
     * @return string The translated string if available, the fallback string otherwise.
     */
    public function get($string, $language = null)
    {
        if (is_null($language))
        {
            $language = $this->_language;
        }

        if (! $this->string_exists($string, $language))
        {
            // Go for Fallback
            $language = $this->_fallback_language;

            if (! $this->string_exists($string, $language))
            {
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
    public function show ($string, $language = null)
    {
        echo $this->get($string, $language);
    }

    /**
     * Returns the entire translation table for the given language
     *
     * @param string $language The language to query
     */
    public function get_stringdb($language)
    {
        $this->_check_for_language($language);
        if (empty($this->_stringdb[$language]))
        {
            return array();
        }
        return $this->_stringdb[$language];
    }

    /**
     * Updates a string in the database. If it does not exist, it will be created
     * automatically.
     *
     * @param string $string        The string-ID to edit.
     * @param string $language        The language to edit.
     * @param string $translation    The UTF-8 encoded string to add/update.
     */
    function update ($string, $language, $translation)
    {
        $this->_check_for_language($language);
        $this->_stringdb[$language][$string] = $translation;
    }

    /**
     * Deletes a string from the database. If the string is not present, it
     * will fail silently.
     *
     * @param string $string        The string-ID to edit.
     * @param string $language        The language to edit.
     */
    function delete ($string, $language)
    {
        // This is error-resilient, deleting a non-existent string will
        // just do nothing.
        unset ($this->_stringdb[$language][$string]);
    }

    /**
     * Scans the current library and delivers all string ids that are in use.
     *
     * @return Array A list of all string-IDs
     */
    function get_all_string_ids()
    {
        $this->_load_all_languages();

        $found_strings = Array();
        foreach ($this->_stringdb as $stringtable)
        {
            $found_strings = array_unique(array_merge(array_keys($stringtable), $found_strings));
        }
        sort($found_strings, SORT_STRING);
        return $found_strings;
    }

    public function get_language_name($lang)
    {
        return $this->_language_db[$lang]['enname'];
    }

    public function get_languages()
    {
        return $this->_language_db;
    }
}
