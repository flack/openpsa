<?php

/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: i18n.php 26637 2010-09-10 14:57:56Z bergie $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This is a basic MidCOM Service which provides an interfaces to the
 * various I18n facilities of MidCOM.
 *
 * The I18n service serves as a central access point for all aspects
 * around internationalization and localization. It provides auto-detection
 * of language data using HTTP Content-Negotiation along with a cookie-based
 * fallback.
 *
 * A good deal of major languages are predefined, see the snippet
 * /lib/midcom/services/_i18n_language-db.dat for details.
 *
 * This class is able to run independently from midcom_application
 * due to the fact that it is used in the cache_hit code.
 *
 * Use this class to set the language preferences (charset and locale) and to gain
 * access to the l10n string databases. A few helper which can be used to ease
 * translation work (like charset conversion) are in here as well.
 *
 * All language codes used here are ISO 639-1 two-letter codes.
 *
 * @package midcom.services
 */
class midcom_services_i18n
{

    /**
     * The language database, loaded from /midcom/config/language_db.inc
     *
     * @var Array
     * @access private
     */
    var $_language_db;

    /**
     * Preferred languages extracted out of the HTTP content negotiation.
     *
     * Array keys are the languages, the value is their q-index.
     *
     * @var Array
     * @access private
     */
    var $_http_lang;

    /**
     * Preferred charsets extracted out of the HTTP content negotiation.
     *
     * Array keys are the charsets, the value is their q-index.
     *
     * @var Array
     * @access private
     */
    var $_http_charset;

    /**
     * Stores the associative array stored in the cookie "midcom_services_i18n"
     *
     * Contains the keys "language" and
     * "charset" or null if the cookie was not set.
     *
     * @var Array
     * @access private
     */
    var $_cookie_data;

    /**
     * Fallback language, in case the selected language is not available.
     *
     * @var string
     * @access private
     */
    var $_fallback_language;

    /**
     * Cache of all instantiated localization classes.
     *
     * They are delivered by reference to all clients.
     *
     * @var Array
     * @access private
     */
    var $_obj_l10n;

    /**
     * Current language.
     *
     * @var string
     * @access private
     */
    var $_current_language;

    /**
     * Current language for content.
     *
     * May be different than the UI language
     *
     * @var string
     * @access private
     */
    var $_current_content_language;

    /**
     * Current character set
     *
     * @var string
     * @access private
     */
    var $_current_charset;

    /**
     * List of different language versions of the site
     *
     * Format: An array indexed by language ID and containing midgard_host objects
     *
     * @var array
     * @access private
     */
    var $_language_hosts = array();

    /**
     * This method initializes the available i18n framework by determining
     * the desired language from these different sources: HTTP Content
     * Negotiation, Client side language cookie.
     *
     * It uses the MidCOM Language database now located at
     * /midcom/services/i18n/_i18n_language-db for any decisions. Its two
     * parameters set the default language in case that none is supplied
     * via HTTP Content Negotiation or through Cookies.
     *
     * The default language set on startup is currently hardcoded to en
     * by the MidCOM core, you should override it after Initialization if you
     * want something else using the setter methods below.
     *
     * The fallback language is read from the MidCOM configuration directive
     * <i>i18n_fallback_language</i>.
     */
    function __construct()
    {
        debug_push_class(__CLASS__, __FUNCTION__);

        $this->_http_lang = Array();
        $this->_http_charset = Array();
        $this->_cookie_data = null;
        $this->_obj_l10n = Array();

        if (!$this->_load_language_db())
        {
            debug_add("Could not load language database. Aborting.", MIDCOM_LOG_CRIT);
            debug_pop();
            return false;
        }

        $this->_fallback_language = $GLOBALS['midcom_config']['i18n_fallback_language'];
        $this->set_language($this->_fallback_language);

        $this->_set_startup_langs();

        debug_pop();
    }

    /**
     * Set output character set.
     *
     * @param string $charset    Charset name.
     */
    function set_charset ($charset)
    {
        $charset = strtolower($charset);
        $this->_current_charset = $charset;
        foreach ($this->_obj_l10n as $name => $object)
        {
            $this->_obj_l10n[$name]->set_charset($charset);
        }
    }

    /**
     * Set output language.
     *
     * This will set the character encoding to the language's default
     * encoding and will also set the system locale to the one
     * specified in the language database.
     *
     * If you want another character encoding as the default one, you
     * have to override it manually using midcom_services_i18n::set_charset()
     * after calling this method.
     *
     * If <i>$switch_content_lang</i> is set, this call will also synchronize
     * the Midgard content language with the MidCOM language.
     *
     * @param string $lang    Language ISO 639-1 code
     * @param boolean $switch_content_lang Whether to switch content language as well
     * @see _synchronize_midgard_language()
     */
    function set_language($lang, $switch_content_lang = false)
    {
        if (!array_key_exists($lang, $this->_language_db))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Language {$lang} not found in the language database.", MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }

        $this->_current_language = $lang;

        if ($switch_content_lang)
        {
            // In the future we may allow changing UI language without changing content language
            $this->_current_content_language = $lang;
        }

        $this->_current_charset = $this->_language_db[$lang]['encoding'];

        /**
         * NOTE: setlocale can take an array of locales as value, it will use
         * the first name valid for the system
         */
        setlocale (LC_ALL, $this->_language_db[$lang]['locale']);

        foreach ($this->_obj_l10n as $name => $object)
        {
            $this->_obj_l10n[$name]->set_language($lang);
        }
    }

    /**
     * Convert the language code to its corresponding ID in Midgard database
     *
     * @static
     * @access public
     * @param String $code    Two-letter code
     * @return int            ID field of the database
     */
    public function code_to_id($code)
    {
        if ($code == '')
        {
            return 0;
        }
        static $cache = array();
        if (!isset($cache[$code]))
        {
            $cache[$code] = null;
            $qb = new midgard_query_builder('midgard_language');
            $qb->add_constraint('code', '=', $code);
            $ret = $qb->execute();
            if ($ret)
            {
                $cache[$code] = $ret[0]->id;
            }
        }
        return $cache[$code];
    }

    /**
     * Convert the ID to its corresponding language code in Midgard database
     *
     * @static
     * @access public
     * @param int $id   ID field of the database
     * @return String Two-letter code
     */
    public function id_to_code($id)
    {
        if ($id == 0)
        {
            return '';
        }
        static $cache = array();
        if (!isset($cache[$id]))
        {
            $cache[$id] = null;
            $lang = new midgard_language($id);
            if ($lang->guid)
            {
                $cache[$id] = $lang->code;
            }
        }
        return $cache[$id];
    }

    /**
     * Set the fallback language.
     *
     * @param string $lang    Language name.
     */
    function set_fallback_language($lang)
    {
        $this->_fallback_language = $lang;
        foreach ($this->_obj_l10n as $name => $object)
        {
            $this->_obj_l10n[$name]->set_fallback_language($lang);
        }
    }

    /**
     * Returns the language database.
     *
     * @return Array
     */
    function get_language_db ()
    {
        return $this->_language_db;
    }

    /**
     * Returns the current language code
     *
     * @return string
     */
    function get_current_language()
    {
        return $this->_current_language;
    }

    /**
     * Returns language code corresponding to current content language
     *
     * @return string
     */
    function get_content_language()
    {
        return $this->get_current_language();
    }

    /**
     * Returns the current fallback language code
     *
     * @return string
     */
    function get_fallback_language ()
    {
        return $this->_fallback_language;
    }

    /**
     * Returns the current character set
     *
     * @return string
     */
    function get_current_charset()
    {
        return $this->_current_charset;
    }

    function get_language_hosts()
    {
        if (count($this->_language_hosts) == 0)
        {
            $qb = new midgard_query_builder('midgard_host');
            $qb->add_constraint('root', '=', $_MIDGARD['page']);

            // TODO: Check online status?

            $hosts = $qb->execute();

            foreach ($hosts as $host)
            {
                $this->_language_hosts[$host->lang] = $host;
            }
        }
        return $this->_language_hosts;
    }

    /**
     * Returns a l10n class instance which can be used to
     * access the localization data of the current component.
     *
     * Using the special name "midcom" you will get the midcom core l10n library.
     *
     * Note that you are receiving a reference here.
     *
     * @see midcom_services__i18n_l10n
     * @param string $component    The component for which to retrieve a string database.
     * @param string $database    The string table to retrieve from the component's locale directory.
     * @return midcom_helper__i18n_l10n    The cached L10n database; honor the reference for memory consumptions sake.
     */
    function get_l10n ($component = 'midcom', $database = 'default')
    {
        $cacheid = "{$component}/{$database}";

        if (! array_key_exists($cacheid, $this->_obj_l10n))
        {
            $this->_load_l10n_db($component, $database);
        }

        return $this->_obj_l10n[$cacheid];
    }

    /**
     * Returns a translated string using the l10n database specified in the function
     * arguments.
     *
     * @param string $stringid The string to translate.
     * @param string $component    The component for which to retrieve a string database. If omitted, this defaults to the
     *     current component (out of the component context).
     * @param string $database    The string table to retrieve from the component's locale directory. If omitted, the 'default'
     *     database is used.
     * @return string The translated string
     * @see midcom_helper__i18n_l10n::get()
     */
    function get_string($stringid, $component = null, $database = 'default')
    {
        if (is_null($component))
        {
            $component = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_COMPONENT);
        }

        $cacheid = "{$component}/{$database}";
        if (! array_key_exists($cacheid, $this->_obj_l10n))
        {
            $this->_load_l10n_db($component, $database);
        }

        return $this->_obj_l10n[$cacheid]->get($stringid);
    }

    /**
     * This is a shortcut for echo $this->get_string(...);.
     *
     * To keep the naming stable with the actual l10n class, this is not called
     * echo_string (Zend won't allow $l10n->echo().)
     *
     * @param string $stringid The string to translate.
     * @param string $component    The component for which to retrieve a string database. If omitted, this defaults to the
     *     current component (out of the component context).
     * @param string $database    The string table to retrieve from the component's locale directory. If omitted, the 'default'
     *     database is used.
     * @see midcom_helper__i18n_l10n::get()
     * @see get_string()
     */
    function show_string($stringid, $component = null, $database = 'default')
    {
        echo $this->get_string($stringid, $component, $database);
    }

    /**
     * Load the specified l10n library.
     *
     * If loading the library failed, generate_error is called, otherwise the l10n
     * db cache is populated accordingly.
     *
     * @param string $component    The component for which to retrieve a string database.
     * @param string $database    The string table to retrieve from the component's locale directory.
     */
    function _load_l10n_db($component, $database)
    {
        $cacheid = "{$component}/{$database}";

        if ($component == 'midcom')
        {
            $obj = new midcom_services__i18n_l10n('midcom', $database);
        }
        else
        {
            $path = str_replace('.', '/', $component);
            $obj = new midcom_services__i18n_l10n($path, $database);
        }

        if (! $obj)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Failed to load L10n database {$cacheid}, see above for possible reasons.", MIDCOM_LOG_ERR);
            $_MIDCOM->generate_error(MIDCOM_LOG_ERROR,
                "Failed to load L10n database {$cacheid}, see the log file for possible reasons.");
            // This will exit.
        }

        $obj->set_language($this->_current_language);
        $obj->set_charset($this->_current_charset);
        $obj->set_fallback_language($this->_fallback_language);
        $this->_obj_l10n[$cacheid] =& $obj;
    }

    /**
     * Scans the HTTP negotiation and the cookie data and tries to set a
     * suitable default language. Cookies have priority here.
     */
    function _set_startup_langs()
    {
        $this->_read_cookie();
        if (!is_null ($this->_cookie_data))
        {
            $this->_current_language = $this->_cookie_data['language'];
            $this->_current_charset = $this->_cookie_data['charset'];
            return;
        }

        $this->_read_http_negotiation();

        if (count ($this->_http_lang) > 0)
        {
            foreach ($this->_http_lang as $name => $q)
            {
                if (array_key_exists($name, $this->_language_db))
                {
                    $this->set_language($name);
                    break;
                }
            }
        }
    }

    /**
     * This method tries to pull the user's preferred language and
     * character set out of a cookie named "midcom_services_i18n".
     */
    function _read_cookie ()
    {
        if (!isset ($_COOKIE))
        {
            return;
        }

        if (!array_key_exists("midcom_services_i18n",$_COOKIE))
        {
            return;
        }

        $rawdata = base64_decode($_COOKIE['midcom_services_i18n']);
        $array = unserialize($rawdata);

        if (   ! array_key_exists('language', $array)
            || ! array_key_exists('charset', $array))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Rejecting cookie, it seems invalid.", MIDCOM_LOG_DEBUG);
            debug_pop();
            return;
        }

        $this->_cookie_data = $array;
        debug_pop();
    }

    /**
     * This method pulls available language and content type data out of
     * the HTTP Headers delivered by the browser and populates the member
     * variables $_http_lang and $_http_content_type.
     *
     * q-parameters for prioritization are supported.
     */
    function _read_http_negotiation ()
    {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
        {
            $accept_langs = $_SERVER['HTTP_ACCEPT_LANGUAGE'];

            $rawdata = explode(",", $accept_langs);
            foreach ($rawdata as $data)
            {
                $params = explode(";",$data);
                $lang = array_shift($params);

                //Fix for Safari
                if (   isset($_SERVER['HTTP_USER_AGENT'])
                    && strstr($_SERVER['HTTP_USER_AGENT'], 'Safari'))
                {
                    $lang = array_shift(explode("-",$lang));
                }

                $q = 1.0;
                $option = array_shift($params);
                while (! is_null($option))
                {
                    $option_params = explode("=", $option);
                    if (count($option_params) != 2)
                    {
                        $option = array_shift($params);
                        continue;
                    }
                    if ($option_params[0] == "q")
                    {
                        $q = $option_params[1];
                        if (!is_numeric($q))
                        {
                            $q = 1.0;
                        }
                        else if ($q > 1.0)
                        {
                            $q = 1.0;
                        }
                        else if ($q < 0.0)
                        {
                            $q = 0.0;
                        }
                    }
                    $option = array_shift($params);
                }
                $this->_http_lang[$lang] = $q;
            }
        }
        arsort($this->_http_lang, SORT_NUMERIC);

        if (isset($_SERVER['HTTP_ACCEPT_CHARSET']))
        {
            $rawdata = explode(",", $_SERVER['HTTP_ACCEPT_CHARSET']);
            foreach ($rawdata as $data)
            {
                $params = explode(";",$data);
                $lang = array_shift($params);
                $q = 1.0;
                $option = array_shift($params);
                while (! is_null($option))
                {
                    $option_params = explode("=",$option);
                    if (count($option_params) != 2)
                    {
                        $option = array_shift($params);
                        continue;
                    }
                    if ($option_params[0] == "q")
                    {
                        $q = $option_params[1];
                        if (!is_numeric($q))
                        {
                            $q = 1.0;
                        }
                        else if ($q > 1.0)
                        {
                            $q = 1.0;
                        }
                        else if ($q < 0.0)
                        {
                            $q = 0.0;
                        }
                    }
                    $option = array_shift($params);
                }
                $this->_http_charset[$lang] = $q;
            }
            arsort ($this->_http_charset, SORT_NUMERIC);
        }
    }

    /**
     * Loads the language database.
     */
    function _load_language_db()
    {
        $path = $GLOBALS['midcom_config']['i18n_language_db_path'];

        if (substr($path, 0, 5) == 'file:')
        {
            $filename = MIDCOM_ROOT . substr($path, 5);
            if (! file_exists($filename))
            {
                $cached_snippets[$path] = null;
                return null;
            }
            $data = file_get_contents($filename);
        }
        else
        {
            $snippet = new midgard_snippet();
            try
            {
                $snippet->get_by_path($path);
            }
            catch (Exception $e)
            {
                $cached_snippets[$path] = null;
                return null;
            }

            if (isset($GLOBALS['midcom_cache']->content))
            {
                $GLOBALS['midcom_cache']->content->register($snippet->guid);
            }

            $data = $snippet->code;
        }

        eval ("\$layout = Array(\n{$data}\n);");
        $this->_language_db = $layout;

        return true;
    }

    /**
     * Lists languages as identifier -> name pairs
     * @return Array
     */
    function list_languages()
    {
        $languages = array();
        foreach ($this->_language_db as $identifier => $language)
        {
            if ($language['enname'] != $language['localname'])
            {
                $languages[$identifier] = "{$language['enname']} ({$language['localname']})";
            }
            else
            {
                $languages[$identifier] = $language['enname'];
            }
        }
        return $languages;
    }

    /**
     * This is a calling wrapper to the iconv library.
     *
     * See the PHP iconv() function for the exact parameter definitions.
     *
     * @param string $source_charset The charset to convert from.
     * @param string $destination_charset The charset to convert to.
     * @param string $string The string to convert.
     * @return mixed The converted string or false on any error.
     */
    function iconv($source_charset, $destination_charset, $string)
    {
        $result = @iconv($source_charset, $destination_charset, $string);
        if (   $result === false
            && strlen($string) > 0)
        {
            debug_add("Iconv returned failed to convert a string, returning an empty string.", MIDCOM_LOG_WARN);
            debug_print_r("Tried to convert this string from {$source_charset} to {$destination_charset}:", $string);
            if (isset($php_errormsg))
            {
                debug_add("Last PHP error was: {$php_errormsg}", MIDCOM_LOG_WARN);
            }
            return false;
        }
        return $result;
    }

    /**
     * This function will convert a string assumed to be in the currently active
     * charset to UTF8.
     *
     * @param string $string The string to convert
     * @return string The string converted to UTF-8
     */
    function convert_to_utf8 ($string)
    {
        if ($this->_current_charset == 'utf-8')
        {
            return $string;
        }
        return $this->iconv($this->_current_charset, 'utf-8', $string);
    }

    /**
     * This function will convert a string assumed to be in UTF-8 to the currently
     * active charset.
     *
     * @param string $string The string to convert
     * @return string The string converted to the current charset
     */
    function convert_from_utf8 ($string)
    {
        if ($this->_current_charset == 'utf-8')
        {
            return $string;
        }
        return $this->iconv('utf-8', $this->_current_charset, $string);
    }

    /**
     * Converts the given string to the current site charset.
     *
     * The charset should be specified explicitly, as autodetection is very
     * very error prone (though sometimes you don't have a choice).
     *
     * @param string $string The string to convert.
     * @param string $charset The charset in which string currently is, omit this parameter to use mb_detect_encoding (error prone!)
     * @return string The converted string.
     */
    function convert_to_current_charset ($string, $charset = null)
    {
        if (is_null($charset))
        {
            // Try to detect source encoding.
            $charset = mb_detect_encoding($string, "UTF-8, UTF-7, ASCII, ISO-8859-15");
            debug_add("mb_detect_encoding got {$charset}");
        }
        return $this->iconv($charset, $this->_current_charset, $string);
    }

    /**
     * Charset-aware replacement of html_entity_decode.
     *
     * Uses Iconv to modify the HTML_ENTITIES translation tables.
     *
     * In addition, this will drop all numeric HTML entities.
     *
     * @param string $text The text with HTML entities, which should be replaced by their native equivalents.
     * @return string The translated string.
     */
    function html_entity_decode($text)
    {
        $trans = array_flip(get_html_translation_table(HTML_ENTITIES));
        if ($this->_current_charset != 'ISO-8859-15')
        {
            foreach ($trans as $key => $value)
            {
                $trans[$key] = iconv('ISO-8859-15', $this->_current_charset, $value);
            }
        }
        $text = strtr($text, $trans);

        // Now convert the numeric entities:
        $search = Array
        (
            '/&#\d{2,5};/ue', // Decimal
            '/&#x([a-fA-F0-7]{2,8});/ue' // Hex
        );
        $replace = Array
        (
            '$this->numeric_entity_decode(\'$0\')',
            '$this->numeric_entity_decode(\'&#\' . hexdec(\'$1\') . \';\')'
        );

        return preg_replace($search, $replace, $text);
    }

    /**
     * This little helper converts a single numeric HTML entity into its
     * current native equivalent.
     *
     * @param string $entity the &# encoded entity (only decimal supported, Hex-
     *     Entities need to be converted with hexdec first.
     * @return string The converted entity into the current charset.
     */
    function numeric_entity_decode ($entity)
    {
        $convmap = array(0x0, 0xFFFFFF, 0, 0xFFFFFF);
        return mb_decode_numericentity($entity, $convmap, $this->_current_charset);
    }
}


?>
