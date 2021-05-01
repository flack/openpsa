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
    /**
     * @var array
     */
    private static $_data = [];

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
        } catch (mgd_exception $e) {
            return null;
        }
        if (!$trusted && !self::verify_password($password, $user->password)) {
            return null;
        }

        if (!$user->login()) {
            return null;
        }
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

    public static function prepare_password(string $password)
    {
        if (midcom::get()->config->get('auth_type') == 'Legacy') {
            return password_hash($password, PASSWORD_DEFAULT);
        }
        if (midcom::get()->config->get('auth_type') == 'SHA256') {
            return hash('sha256', $password);
        }

        return $password;
    }

    public static function is_user($person) : bool
    {
        if (empty($person->guid)) {
            return false;
        }
        $qb = new midgard_query_builder('midgard_user');
        $qb->add_constraint('person', '=', $person->guid);
        return $qb->count() > 0;
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
     * Check if the current user is admin
     */
    public static function is_admin() : bool
    {
        if ($user = midgard_connection::get_instance()->get_user()) {
            return $user->is_admin();
        }
        return false;
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
        if (isset(self::$_data[$key])) {
            return self::$_data[$key];
        }

        return null;
    }

    /**
     * Lists all available MgdSchema types
     */
    public static function get_schema_types() : array
    {
        if (!isset(self::$_data['schema_types'])) {
            $classnames = connection::get_em()->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();

            self::$_data['schema_types'] = array_filter($classnames, function($input) {
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
            $url_components = parse_url("http://openpsa2.org{$_SERVER['REQUEST_URI']}");
            if ($self !== '/') {
                $url_components['path'] = preg_replace('|^' . $self . '|', '/', $url_components['path']);
            }
            self::_parse_url($url_components['path'], $self, substr($self, 0, -1));
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
        $uri = preg_replace('/\/[\/]+/i', '/', $uri);
        $path_parts = explode('/', $uri);
        $page_style = '';
        $path = $self;

        self::$_data['argv'] = [];
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
            } else {
                self::$_data['argv'][] = $part;
                $path .= $part . '/';
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
        $path_array = explode('/', midcom::get()->config->get('theme'));

        while (!empty($path_array)) {
            $theme_path = implode('/', $path_array);
            if (is_dir(OPENPSA2_THEME_ROOT . $theme_path . '/style/' . $page_name)) {
                return true;
            }
            array_pop($path_array);
        }
        return false;
    }

    public static function get_unique_host_name() : string
    {
        if (null === self::_get('unique_host_name')) {
            self::$_data['unique_host_name'] = str_replace(':', '_', $_SERVER['SERVER_NAME']) . '_' . str_replace('/', '_', self::get_url('prefix'));
        }

        return self::$_data['unique_host_name'];
    }
}
