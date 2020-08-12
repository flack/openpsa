<?php
/**
 * @package midcom.compat
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Support for interactions with environment
 *
 * @package midcom.compat
 */
class midcom_compat_environment
{
    private static $_headers = [];

    private static $_implementation;

    public static function get() : self
    {
        return self::$_implementation;
    }

    public static function initialize()
    {
        self::$_implementation = new static;
    }

    public function header(string $string, bool $replace = true, int $http_response_code = null)
    {
        if (!defined('OPENPSA2_UNITTEST_RUN')) {
            header($string, $replace, $http_response_code);
        } else {
            self::$_headers[] = [
                'value' => $string,
                'replace' => $replace,
                'http_response_code' => $http_response_code
            ];
        }
    }

    public function stop_request(string $message)
    {
        if (!defined('OPENPSA2_UNITTEST_RUN')) {
            exit($message);
        }
    }

    public static function flush_registered_headers() : array
    {
        $headers = self::$_headers;
        self::$_headers = [];
        return $headers;
    }
}
