<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\Intl\Intl;
use Symfony\Component\Intl\Locales;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;

/**
 * This is a basic MidCOM Service which provides an interfaces to the
 * various I18n facilities of MidCOM.
 *
 * The I18n service serves as a central access point for all aspects
 * around internationalization and localization. It provides auto-detection
 * of language data using HTTP Content-Negotiation along with a cookie-based
 * fallback.
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
     * Fallback language, in case the selected language is not available.
     *
     * @var string
     */
    private $_fallback_language;

    /**
     * Cache of all instantiated localization classes.
     *
     * @var midcom_services_i18n_l10n[]
     */
    private $_obj_l10n = [];

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
    private $_current_charset = 'utf-8';

    /**
     * Initialize the available i18n framework by determining the desired language
     * from these different sources: HTTP Content Negotiation, Client side language cookie.
     *
     * Its two parameters set the default language in case that none is supplied
     * via HTTP Content Negotiation or through Cookies.
     *
     * The default language set on startup is currently hardcoded to 'en',
     * you should override it after initialization, if you want something
     * else using the setter methods below.
     *
     * The fallback language is read from the MidCOM configuration directive
     * <i>i18n_fallback_language</i>.
     */
    public function __construct(RequestStack $request_stack)
    {
        $this->_fallback_language = midcom::get()->config->get('i18n_fallback_language');

        $found = false;
        if ($request = $request_stack->getCurrentRequest()) {
            $found = $this->_read_cookie($request) || $this->_read_http_negotiation($request);
        }
        if (!$found) {
            $this->set_language($this->_fallback_language);
        }
    }

    /**
     * Try to pull the user's preferred language and
     * character set out of a cookie named "midcom_services_i18n".
     */
    private function _read_cookie(Request $request) : bool
    {
        if (!$request->cookies->has('midcom_services_i18n')) {
            return false;
        }

        $rawdata = base64_decode($request->cookies->get('midcom_services_i18n'));
        $array = unserialize($rawdata);

        if (   !array_key_exists('language', $array)
            || !array_key_exists('charset', $array)) {
            debug_add("Rejecting cookie, it seems invalid.");
            return false;
        }
        $this->set_charset($array['charset']);
        return $this->set_language($array['language']);
    }

    /**
     * Pull available language out of the HTTP Headers
     *
     * q-parameters for prioritization are supported.
     */
    private function _read_http_negotiation(Request $request) : bool
    {
        if (!$request->server->has('HTTP_ACCEPT_LANGUAGE')) {
            return false;
        }
        $rawdata = explode(",", $request->server->get('HTTP_ACCEPT_LANGUAGE'));
        $http_langs = [];
        foreach ($rawdata as $data) {
            $params = explode(";", $data);
            $lang = array_shift($params);

            // we can't use strings like en-US, so we only use the first two characters
            $lang = substr($lang, 0, 2);
            $q = $this->_get_q($params);

            if (   !isset($http_langs[$lang])
                || $http_langs[$lang] < $q) {
                $http_langs[$lang] = $q;
            }
        }
        arsort($http_langs, SORT_NUMERIC);
        foreach (array_keys($http_langs) as $name) {
            if ($this->set_language($name)) {
                return true;
            }
        }

        return false;
    }

    private function _get_q(array $params) : float
    {
        $q = 1.0;
        $option = array_shift($params);
        while ($option !== null) {
            $option_params = explode("=", $option);
            if (count($option_params) != 2) {
                $option = array_shift($params);
                continue;
            }
            if (   $option_params[0] == "q"
                && is_numeric($option_params[1])) {
                // make sure that 0.0 <= $q <= 1.0
                $q = max(0.0, min(1.0, $option_params[1]));
            }
            $option = array_shift($params);
        }
        return $q;
    }

    /**
     * Set output character set.
     *
     * @param string $charset    Charset name.
     */
    public function set_charset(string $charset)
    {
        $this->_current_charset = strtolower($charset);
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
     */
    public function set_language(string $lang) : bool
    {
        if (Locales::getName($lang) === null) {
            debug_add("Language {$lang} not found.", MIDCOM_LOG_ERROR);
            return false;
        }

        $this->_current_language = $lang;

        setlocale(LC_ALL, $lang);
        if (Intl::isExtensionLoaded()) {
            Locale::setDefault($lang);
        }

        foreach ($this->_obj_l10n as $object) {
            $object->set_language($lang);
        }
        return true;
    }

    /**
     * Set the fallback language.
     *
     * @param string $lang    Language name.
     */
    public function set_fallback_language(string $lang)
    {
        $this->_fallback_language = $lang;
        foreach ($this->_obj_l10n as $object) {
            $object->set_fallback_language($lang);
        }
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

    /**
     * Returns a l10n class instance which can be used to
     * access the localization data of the current component.
     *
     * If loading failed, midcom_error is thrown, otherwise the l10n
     * db cache is populated accordingly.
     *
     * @see midcom_services_i18n_l10n
     * @param string $component    The component for which to retrieve a string database.
     * @param string $database    The string table to retrieve from the component's locale directory.
     */
    public function get_l10n(string $component = 'midcom', string $database = 'default') : midcom_services_i18n_l10n
    {
        $cacheid = "{$component}/{$database}";

        if (!array_key_exists($cacheid, $this->_obj_l10n)) {
            $obj = new midcom_services_i18n_l10n($component, $database);
            $obj->set_language($this->_current_language);
            $obj->set_fallback_language($this->_fallback_language);
            $this->_obj_l10n[$cacheid] = $obj;
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
     * @see midcom_services_i18n_l10n::get()
     */
    public function get_string(string $stringid, $component = null, string $database = 'default') : string
    {
        if ($component === null) {
            $component = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_COMPONENT) ?? 'midcom';
        }

        return $this->get_l10n($component, $database)->get($stringid);
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
    public function show_string(string $stringid, $component = null, string $database = 'default')
    {
        echo $this->get_string($stringid, $component, $database);
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
    private function iconv(string $source_charset, string $destination_charset, string $string)
    {
        $result = @iconv($source_charset, $destination_charset, $string);
        if ($result === false && !empty($string)) {
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
    public function convert_to_utf8(string $string)
    {
        if ($this->_current_charset == 'utf-8') {
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
    public function convert_from_utf8(string $string)
    {
        if ($this->_current_charset == 'utf-8') {
            return $string;
        }
        return $this->iconv('utf-8', $this->_current_charset, $string);
    }

    /**
     * Converts the given string to the current site charset.
     *
     * @param string $string The string to convert.
     * @return string The converted string.
     */
    public function convert_to_current_charset(string $string)
    {
        $charset = mb_detect_encoding($string, "UTF-8, UTF-7, ASCII, ISO-8859-15");
        debug_add("mb_detect_encoding got {$charset}");
        return $this->iconv($charset, $this->_current_charset, $string);
    }

    /**
     * Wrapped html_entity_decode call
     *
     * @param string $text The text with HTML entities, which should be replaced by their native equivalents.
     */
    public function html_entity_decode(string $text) : string
    {
        return html_entity_decode($text, ENT_COMPAT, $this->_current_charset);
    }
}
