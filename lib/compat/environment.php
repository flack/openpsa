<?php
/**
 * @package midcom.compat
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Interface for interactions with environment (PHP SAPI, framework, Midgard version, etc)
 *
 * @package midcom.compat
 */
abstract class midcom_compat_environment
{
    private static $_implementation;

    public static function get()
    {
        return self::$_implementation;
    }

    public static function initialize()
    {
        if (defined('OPENPSA2_UNITTEST_RUN'))
        {
            self::$_implementation = new midcom_compat_unittest();
        }
        else
        {
            self::$_implementation = new midcom_compat_default();
        }
    }

    abstract public function header($string, $replace = true, $http_response_code = null);

    abstract public function stop_request($message = '');

    abstract public function headers_sent();

    abstract public function setcookie($name, $value = '', $expire = 0, $path = '/', $domain = null, $secure = false, $httponly = false);
}

/**
 * Default environment support
 *
 * @package midcom.compat
 */
class midcom_compat_default extends midcom_compat_environment
{
    public function __construct()
    {
        if (   php_sapi_name() != 'cli'
            || !empty($_SERVER['REMOTE_ADDR']))
        {
            $this->_httpd_setup();
        }
    }

    private function _httpd_setup()
    {
        /*
         * Second, make sure the URLs not having query string (or midcom-xxx- -method signature)
         * have trailing slash or some extension in the "filename".
         *
         * This makes life much, much better when making static copies for whatever reason
         *
         * 2008-09-26: Now also rewrites urls ending in .html to end with trailing slash.
         */
        $redirect_test_uri = (string)$_SERVER['REQUEST_URI'];
        if (   !isset($_SERVER['MIDCOM_COMPAT_REDIR'])
            || (bool)$_SERVER['MIDCOM_COMPAT_REDIR'] !== false)
        {
            $redirect_test_uri = preg_replace('/\.html$/', '', $redirect_test_uri);
        }
        if (   !preg_match('%\?|/$|midcom-.+-|/.*\.[^/]+$%', $redirect_test_uri)
            && (empty($_POST)))
        {
            $this->header('HTTP/1.0 301 Moved Permanently');
            $this->header("Location: {$redirect_test_uri}/");
            $redirect_test_uri_clean = htmlentities($redirect_test_uri);
            echo "301: new location <a href='{$redirect_test_uri_clean}/'>{$redirect_test_uri_clean}/</a>";
            $this->stop_request();
        }
        // Advertise the fact that this is a Midgard server
        $this->header('X-Powered-By: Midgard/' . mgd_version());
    }

    public function header($string, $replace = true, $http_response_code = null)
    {
        header($string, $replace, $http_response_code);
    }

    public function stop_request($message = '')
    {
        exit($message);
    }

    public function headers_sent()
    {
        return headers_sent();
    }

    public function setcookie($name, $value = '', $expire = 0, $path = '/', $domain = null, $secure = false, $httponly = false)
    {
        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }
}

/**
 * Support for running under PHPunit
 *
 * @package midcom.compat
 */
class midcom_compat_unittest extends midcom_compat_environment
{
    private static $_headers = array();

    public function __construct() {}

    public function header($string, $replace = true, $http_response_code = null)
    {
        if (preg_match('/^Location: (.*?)$/', $string, $matches))
        {
            throw new openpsa_test_relocate($matches[1], $http_response_code);
        }
        self::$_headers[] = array
        (
            'value' => $string,
            'replace' => $replace,
            'http_response_code' => $http_response_code
        );
    }

    public function stop_request($message = '') {}

    public function headers_sent() {}

    public function setcookie($name, $value = '', $expire = 0, $path = '/', $domain = null, $secure = false, $httponly = false) {}

    public static function flush_registered_headers()
    {
        $headers = self::$_headers;
        self::$_headers = array();
        return $headers;
    }
}
