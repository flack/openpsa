<?php
/**
 * @package midcom
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midgard\portable\storage\connection;
use midgard\portable\api\error\exception as mgd_exception;
use midgard\portable\api\mgdobject;

/**
 * Wrapper for Midgard-related functionality
 *
 * @package midcom
 */
class midcom_connection
{
    private static array $_data = [];

    /**
     * DB connection setup routine
     *
     * @throws Exception We use regular exceptions here, because this might run before things are properly set up
     */
    public static function setup(string $basedir) : bool
    {
        if (file_exists($basedir . 'config/midgard-portable.inc.php')) {
            include $basedir . 'config/midgard-portable.inc.php';
            return midgard_connection::get_instance()->is_connected();
        }
        if (file_exists($basedir . 'config/midgard-portable-default.inc.php')) {
            include $basedir . 'config/midgard-portable-default.inc.php';
            //default config has in-memory db, so all the tables may be missing
            return midgard_storage::class_storage_exists('midgard_user');
        }
        throw new Exception("Could not connect to database, configuration file not found");
    }

    /**
     * Set Midgard log level
     *
     * @param string $loglevel Midgard log level
     */
    static function set_loglevel($loglevel) : bool
    {
        return midgard_connection::get_instance()->set_loglevel($loglevel);
    }

    /**
     * Set Midgard error code
     */
    public static function set_error(int $errorcode)
    {
        midgard_connection::get_instance()->set_error($errorcode);
    }

    /**
     * Get Midgard error code
     */
    public static function get_error() : int
    {
        return midgard_connection::get_instance()->get_error();
    }

    /**
     * Get Midgard error message
     */
    public static function get_error_string() : string
    {
        return midgard_connection::get_instance()->get_error_string();
    }

    /**
     * Perform a login against the midgard backend
     */
    public static function login(string $username, string $password, bool $trusted = false) : ?midgard_user
    {
        $login_tokens = [
            'login' => $username,
            'authtype' => midcom::get()->config->get('auth_type'),
        ];

        try {
            $user = new midgard_user($login_tokens);
        } catch (mgd_exception) {
            return null;
        }
        if (!$trusted && !self::verify_password($password, $user->password)) {
            return null;
        }

        $user->login();
        return $user;
    }

    public static function verify_password(string $password, string $hash) : bool
    {
        if (midcom::get()->config->get('auth_type') == 'Legacy') {
            return password_verify($password, $hash);
        }
        if (midcom::get()->config->get('auth_type') == 'SHA256') {
            $password = hash('sha256', $password);
        }

        return $password === $hash;
    }

    public static function prepare_password(string $password) : string
    {
        if (midcom::get()->config->get('auth_type') == 'Legacy') {
            return password_hash($password, PASSWORD_DEFAULT);
        }
        if (midcom::get()->config->get('auth_type') == 'SHA256') {
            return hash('sha256', $password);
        }

        return $password;
    }

    /**
     * Get current Midgard user ID
     */
    public static function get_user() : int
    {
        if ($user = midgard_connection::get_instance()->get_user()) {
            return $user->get_person()->id;
        }
        return 0;
    }

    /**
     * Logout the current user, if any
     */
    public static function logout()
    {
        if ($user = midgard_connection::get_instance()->get_user()) {
            $user->logout();
        }
    }

    private static function _get(string $key)
    {
        return self::$_data[$key] ?? null;
    }

    /**
     * Lists all available MgdSchema types
     */
    public static function get_schema_types() : array
    {
        if (!isset(self::$_data['schema_types'])) {
            $classnames = connection::get_em()->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();

            self::$_data['schema_types'] = array_filter($classnames, function(string $input) {
                return is_subclass_of($input, mgdobject::class);
            });
        }
        return self::$_data['schema_types'];
    }

    /**
     * Get various pieces of information extracted from the URL
     *
     * @return mixed The data for the requested key or null if it doesn't exist
     */
    public static function get_url(string $key)
    {
        static $parsed = false;
        if (!$parsed) {
            $self = defined('OPENPSA2_PREFIX') ? OPENPSA2_PREFIX : '/';

            // we're only interested in the path, so use a dummy domain for simplicity's sake
            $url_path = (string) parse_url("https://openpsa2.org{$_SERVER['REQUEST_URI']}", PHP_URL_PATH);
            if ($self !== '/') {
                $url_path = preg_replace('|^' . $self . '|', '/', $url_path);
            }
            self::_parse_url($url_path, $self, substr($self, 0, -1));
            $parsed = true;
        }

        return self::_get($key);
    }

    /**
     * Enables themes to have subdirectories (which have a similar effect to mgd1 pages)
     *
     * @param string $uri The request path
     * @param string $self The instance's root URL
     * @param string $prefix The root URL's prefix, if any (corresponds to mgd1 host)
     */
    private static function _parse_url(string $uri, string $self, string $prefix)
    {
        $path_parts = explode('/', $uri);
        $page_style = '';
        $path = $uri;

        $args_started = false;
        foreach ($path_parts as $part) {
            if ($part === '') {
                continue;
            }
            if (    midcom::get()->config->get('theme')
                && !$args_started
                && self::check_page_exists($part)) {
                $page_style .= '/' . $part;
                $self .= $part . '/';
                $path = substr($path, strlen($part) + 1);
            } else {
                $args_started = true;
            }
        }

        self::$_data['page_style'] = $page_style;
        self::$_data['uri'] = $path;
        self::$_data['self'] = $self;
        self::$_data['prefix'] = $prefix;
    }

    /**
     * Iterate through possible page directories in style-tree
     * and check if the page exists (as a folder).
     */
    private static function check_page_exists(string $page_name) : bool
    {
        $prefix = midcom::get()->getProjectDir() . '/var/themes/';
        $path_array = explode('/', midcom::get()->config->get('theme'));
        while (!empty($path_array)) {
            $theme_path = implode('/', $path_array);
            if (is_dir($prefix . $theme_path . '/style/' . $page_name)) {
                return true;
            }
            array_pop($path_array);
        }
        return false;
    }
}
