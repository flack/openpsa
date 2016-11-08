<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
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
 * /lib/midcom/config/language_db.inc for details.
 *
 * This class is able to run independently from midcom_application
 * due to the fact that it is used in the cache_hit code.
 *
 * Use this class to set the language preferences (charset and locale) and to gain
 * access to the l10n string databases. A few helpers which can be used to ease
 * translation work (like charset conversion) are in here as well.
 *
 * All language codes used here are ISO 639-1 two-letter codes.
 *
 * @package midcom.services
 */
class midcom_services_i18n
{
    /**
     * The language database, loaded from /lib/midcom/config/language_db.inc
     *
     * @var Array
     */
    private $_language_db;

    /**
     * Preferred languages extracted out of the HTTP content negotiation.
     *
     * Array keys are the languages, the value is their q-index.
     *
     * @var Array
     */
    private $_http_lang = array();

    /**
     * Preferred charsets extracted out of the HTTP content negotiation.
     *
     * Array keys are the charsets, the value is their q-index.
     *
     * @var Array
     */
    private $_http_charset = array();

    /**
     * Fallback language, in case the selected language is not available.
     *
     * @var string
     */
    private $_fallback_language;

    /**
     * Cache of all instantiated localization classes.
     *
     * @var Array
     */
    private $_obj_l10n = array();

    /**
     * Current language.
     *
     * @var string
     */
    private $_current_language;

    /**
     * Current character set
     *
     * @var string
     */
    private $_current_charset;

    /**
     * List of different language versions of the site
     *
     * Format: An array indexed by language ID and containing midgard_host objects
     *
     * @var array
     */
    private $_language_hosts = array();

    /**
     * Initialize the available i18n framework by determining the desired language
     * from these different sources: HTTP Content Negotiation, Client side language cookie.
     *
     * It uses the MidCOM Language database now located at
     * /lib/midcom/config/language-db.inc for any decisions. Its two
     * parameters set the default language in case that none is supplied
     * via HTTP Content Negotiation or through Cookies.
     *
     * The default language set on startup is currently hardcoded to 'en',
     * you should override it after initialization, if you want something
     * else using the setter methods below.
     *
     * The fallback language is read from the MidCOM configuration directive
     * <i>i18n_fallback_language</i>.
     */
    public function __construct()
    {
        if (!$this->_load_language_db())
        {
            debug_add("Could not load language database. Aborting.", MIDCOM_LOG_CRIT);
            return false;
        }

        $this->_fallback_language = midcom::get()->config->get('i18n_fallback_language');
        $this->set_language($this->_fallback_language);

        $this->_set_startup_langs();
    }

    /**
     * Set output character set.
     *
     * @param string $charset    Charset name.
     */
    public function set_charset ($charset)
    {
        $charset = strtolower($charset);
        $this->_current_charset = $charset;
        foreach ($this->_obj_l10n as $object)
        {
            $object->set_charset($charset);
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
     * @param string $lang    Language ISO 639-1 code
     * @see _synchronize_midgard_language()
     */
    public function set_language($lang)
    {
        if (!array_key_exists($lang, $this->_language_db))
        {
            debug_add("Language {$lang} not found in the language database.", MIDCOM_LOG_ERROR);
            return false;
        }

        $this->_current_language = $lang;
        $this->_current_charset = strtolower($this->_language_db[$lang]['encoding']);

        /**
         * NOTE: setlocale can take an array of locales as value, it will use
         * the first name valid for the system
         */
        setlocale (LC_ALL, $this->_language_db[$lang]['locale']);

        foreach ($this->_obj_l10n as $object)
        {
            $object->set_language($lang);
        }
    }

    /**
     * Convert the language code to its corresponding ID in Midgard database
     *
     * @param string $code    Two-letter code
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
            if (!empty($ret))
            {
                $cache[$code] = $ret[0]->id;
            }
        }
        return $cache[$code];
    }

    /**
     * Convert the ID to its corresponding language code in Midgard database
     *
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
            try
            {
                $lang = new midgard_language($id);
                $cache[$id] = $lang->code;
            }
            catch (midcom_error $e)
            {
                return '';
            }
        }
        return $cache[$id];
    }

    /**
     * Set the fallback language.
     *
     * @param string $lang    Language name.
     */
    public function set_fallback_language($lang)
    {
        $this->_fallback_language = $lang;
        foreach ($this->_obj_l10n as $object)
        {
            $object->set_fallback_language($lang);
        }
    }

    /**
     * Returns the language database.
     *
     * @return Array
     */
    function get_language_db()
    {
        return $this->_language_db;
    }

    /**
     * Returns the current language code
     *
     * @return string
     */
    public function get_current_language()
    {
        return $this->_current_language;
    }

    /**
     * Returns language code corresponding to current content language
     *
     * @return string
     */
    public function get_content_language()
    {
        return $this->get_current_language();
    }

    /**
     * Returns the current fallback language code
     *
     * @return string
     */
    public function get_fallback_language()
    {
        return $this->_fallback_language;
    }

    /**
     * Returns the current character set
     *
     * @return string
     */
    public function get_current_charset()
    {
        return $this->_current_charset;
    }

    function get_language_hosts()
    {
        if (count($this->_language_hosts) == 0)
        {
            $qb = new midgard_query_builder('midgard_host');
            $qb->add_constraint('root', '=', midcom_connection::get('page'));

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
     * @see midcom_services_i18n_l10n
     * @param string $component    The component for which to retrieve a string database.
     * @param string $database    The string table to retrieve from the component's locale directory.
     * @return midcom_services_i18n_l10n    The cached L10n database; honor the reference for memory consumptions sake.
     */
    public function get_l10n($component = 'midcom', $database = 'default')
    {
        $cacheid = "{$component}/{$database}";

        if (!array_key_exists($cacheid, $this->_obj_l10n))
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
     * @see midcom_services_i18n_l10n::get()
     */
    public function get_string($stringid, $component = null, $database = 'default')
    {
        if (is_null($component))
        {
            $component = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_COMPONENT);
        }

        $cacheid = "{$component}/{$database}";
        if (!array_key_exists($cacheid, $this->_obj_l10n))
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
     * @see midcom_services_i18n_l10n::get()
     * @see get_string()
     */
    public function show_string($stringid, $component = null, $database = 'default')
    {
        echo $this->get_string($stringid, $component, $database);
    }

    /**
     * Load the specified l10n library.
     *
     * If loading the library failed, midcom_error is thrown, otherwise the l10n
     * db cache is populated accordingly.
     *
     * @param string $component    The component for which to retrieve a string database.
     * @param string $database    The string table to retrieve from the component's locale directory.
     */
    private function _load_l10n_db($component, $database)
    {
        $cacheid = "{$component}/{$database}";
        $obj = new midcom_services_i18n_l10n($component, $database);

        $obj->set_language($this->_current_language);
        $obj->set_charset($this->_current_charset);
        $obj->set_fallback_language($this->_fallback_language);
        $this->_obj_l10n[$cacheid] = $obj;
    }

    /**
     * Scans the HTTP negotiation and the cookie data and tries to set a
     * suitable default language. Cookies have priority here.
     */
    private function _set_startup_langs()
    {
        $cookie_data = $this->_read_cookie();
        if (!is_null($cookie_data))
        {
            $this->_current_language = $cookie_data['language'];
            $this->_current_charset = $cookie_data['charset'];
            return;
        }

        $this->_read_http_negotiation();

        if (count ($this->_http_lang) > 0)
        {
            foreach (array_keys($this->_http_lang) as $name)
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
     * Try to pull the user's preferred language and
     * character set out of a cookie named "midcom_services_i18n".
     */
    private function _read_cookie()
    {
        if (empty($_COOKIE['midcom_services_i18n']))
        {
            return;
        }

        $rawdata = base64_decode($_COOKIE['midcom_services_i18n']);
        $array = unserialize($rawdata);

        if (   !array_key_exists('language', $array)
            || !array_key_exists('charset', $array))
        {
            debug_add("Rejecting cookie, it seems invalid.");
            return;
        }

        return $array;
    }

    /**
     * Pull available language and content type data out of the HTTP Headers delivered
     * by the browser and populate the member variables $_http_lang and $_http_content_type.
     *
     * q-parameters for prioritization are supported.
     */
    private function _read_http_negotiation()
    {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
        {
            $accept_langs = $_SERVER['HTTP_ACCEPT_LANGUAGE'];

            $rawdata = explode(",", $accept_langs);
            foreach ($rawdata as $data)
            {
                $params = explode(";", $data);
                $lang = array_shift($params);

                // we can't use strings like en-US, so we only use the first two characters
                $lang = substr($lang, 0, 2);
                $q = $this->_get_q($params);

                if (   !isset($this->_http_lang[$lang])
                    || $this->_http_lang[$lang] < $q)
                {
                    $this->_http_lang[$lang] = $q;
                }
            }
        }
        arsort($this->_http_lang, SORT_NUMERIC);

        if (isset($_SERVER['HTTP_ACCEPT_CHARSET']))
        {
            $rawdata = explode(",", $_SERVER['HTTP_ACCEPT_CHARSET']);
            foreach ($rawdata as $data)
            {
                $params = explode(";", $data);
                $lang = array_shift($params);
                $q = $this->_get_q($params);

                $this->_http_charset[$lang] = $q;
            }
            arsort ($this->_http_charset, SORT_NUMERIC);
        }
    }

    private function _get_q(array $params)
    {
        $q = 1.0;
        $option = array_shift($params);
        while (!is_null($option))
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
                else
                {
                    //make sure that 0.0 <= $q <= 1.0
                    $q = max(0.0, min(1.0, $q));
                }
            }
            $option = array_shift($params);
        }
        return $q;
    }

    /**
     * Loads the language database.
     */
    private function _load_language_db()
    {
        $path = midcom::get()->config->get('i18n_language_db_path');
        try
        {
            $data = midcom_helper_misc::get_snippet_content($path);
            $this->_language_db = midcom_helper_misc::parse_config($data);
            return true;
        }
        catch (midcom_error $e)
        {
            $e->log();
            return false;
        }
    }

    /**
     * Lists languages as identifier -> name pairs
     *
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
            midcom::get()->debug->log_php_error(MIDCOM_LOG_WARN);
            return false;
        }
        return $result;
    }

    /**
     * Convert a string assumed to be in the currently active charset to UTF8.
     *
     * @param string $string The string to convert
     * @return string The string converted to UTF-8
     */
    public function convert_to_utf8($string)
    {
        if ($this->_current_charset == 'utf-8')
        {
            return $string;
        }
        return $this->iconv($this->_current_charset, 'utf-8', $string);
    }

    /**
     * Convert a string assumed to be in UTF-8 to the currently active charset.
     *
     * @param string $string The string to convert
     * @return string The string converted to the current charset
     */
    public function convert_from_utf8($string)
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
    function convert_to_current_charset($string, $charset = null)
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
     * Wrapped html_entity_decode call
     *
     * @param string $text The text with HTML entities, which should be replaced by their native equivalents.
     * @return string The translated string.
     */
    function html_entity_decode($text)
    {
        return html_entity_decode($text, ENT_COMPAT, $this->_current_charset);
    }
}
