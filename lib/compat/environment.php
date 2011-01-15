<?php
/**
 * @package midcom
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Interface for interactions with environment (PHP SAPI, framework, Midgard version, etc)
 *
 * @package midcom
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
        if (extension_loaded('midgard2'))
        {
            //we need to provide replacements for some deprecated mgd1 functions
            require('midgard1.php');
        }
        require('ragnaroek.php');

        if (class_exists('midgardmvc_core'))
        {
            self::$_implementation = new midcom_compat_ragnaland();
        }
        else
        {
            self::$_implementation = new midcom_compat_default();
        }
    }

    public abstract function header($string, $replace = true, $http_response_code = null);

    public abstract function stop_request($message = '');

    public abstract function headers_sent();

    public abstract function setcookie();
}

/**
 * Default environment support
 *
 * @package midcom
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
         * First, block all Link prefetching requests as long as
         * MidCOM isn't bulletproofed against this "feature".
         * Ultimately, this is also a matter of performance...
         */
        if (   array_key_exists('HTTP_X_MOZ', $_SERVER)
            && $_SERVER['HTTP_X_MOZ'] == 'prefetch')
        {
            $this->header('Cache-Control: no-cache');
            $this->header('Pragma: no-cache');
            $this->header('HTTP/1.0 403 Forbidden');
            echo '403: Forbidden<br><br>Prefetching not allowed.';
            $this->stop_request();
        }

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
            && (   !isset($_POST)
                || empty($_POST))
            )
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
        if ($http_response_code === null)
        {
            header($string, $replace);
        }
        else
        {
            header($string, $replace, $http_response_code);
        }
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
        if (version_compare(PHP_VERSION, '5.2.0', '<'))
        {
            return setcookie($name, $value, $expire, $path, $domain, $secure);
        }
        else
        {
            return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
        }
    }
}

/**
 * MidgardMVC Ragnaland support
 *
 * @package midcom
 */
class midcom_compat_ragnaland extends midcom_compat_default
{
    public function header($string, $replace = true, $http_response_code = null)
    {
        if ($http_response_code === null)
        {
            midgardmvc_core::get_instance()->dispatcher->header($string, $replace);
        }
        else
        {
            midgardmvc_core::get_instance()->dispatcher->header($string, $replace, $http_response_code);
        }
    }

    public function stop_request($message = '')
    {
        midgardmvc_core::get_instance()->dispatcher->end_request();
    }

    public function headers_sent()
    {
        return midgardmvc_core::get_instance()->dispatcher->headers_sent();
    }

    public function setcookie($name, $value = '', $expire = 0, $path = '/', $domain = null, $secure = false, $httponly = false)
    {
        return midgardmvc_core::get_instance()->dispatcher->setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }
}
?>