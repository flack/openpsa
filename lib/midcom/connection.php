<?php
/**
 * @package midcom
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midgard\portable\storage\connection;
use midgard\portable\api\error\exception as mgd_exception;

/**
 * Wrapper for Midgard-related functionality
 *
 * @package midcom
 */
class midcom_connection
{
    /**
     * Private cache for connection information
     *
     * @var array
     */
    private static $_data = [];

    /**
     * DB connection setup routine
     *
     * @param string $basedir The directory to look for config files if necessary
     * @throws Exception We use regular exceptions here, because this might run before things are properly set up
     * @return boolean Indicating success
     */
    public static function setup($basedir = null)
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
     * Check whether Midgard database connection exists
     *
     * @return boolean
     */
    static function is_connected()
    {
        return midgard_connection::get_instance()->is_connected();
    }

    /**
     * Set Midgard log level
     *
     * @param string $loglevel Midgard log level
     */
    static function set_loglevel($loglevel)
    {
        return midgard_connection::get_instance()->set_loglevel($loglevel);
    }

    /**
     * Set Midgard error code
     *
     * @param int $errorcode Midgard error code
     */
    public static function set_error($errorcode)
    {
        return midgard_connection::get_instance()->set_error($errorcode);
    }

    /**
     * Get Midgard error code
     *
     * @return int Midgard error code
     */
    public static function get_error()
    {
        return midgard_connection::get_instance()->get_error();
    }

    /**
     * Get Midgard error message
     *
     * @return string Midgard error message
     */
    public static function get_error_string()
    {
        return midgard_connection::get_instance()->get_error_string();
    }

    /**
     * Perform a login against the midgard backend
     *
     * @param string $username The username as entered
     * @param string $password The password as entered
     * @param boolean $trusted Use trusted auth
     * @return mixed The appropriate object or false
     */
    public static function login($username, $password, $trusted = false)
    {
        $login_tokens = [
            'login' => $username,
            'authtype' => midcom::get()->config->get('auth_type'),
        ];

        try {
            $user = new midgard_user($login_tokens);
        } catch (mgd_exception $e) {
            return false;
        }
        if (!$trusted && !self::verify_password($password, $user->password)) {
            return false;
        }

        if (!$user->login()) {
            return false;
        }
        return $user;
    }

    public static function verify_password($password, $hash)
    {
        if (midcom::get()->config->get('auth_type') == 'Legacy') {
            return password_verify($password, $hash);
        }
        if (midcom::get()->config->get('auth_type') == 'SHA256') {
            $password = hash('sha256', $password);
        }

        return $password === $hash;
    }

    public static function prepare_password($password)
    {
        if (midcom::get()->config->get('auth_type') == 'Legacy') {
            return password_hash($password, PASSWORD_DEFAULT);
        }
        if (midcom::get()->config->get('auth_type') == 'SHA256') {
            return hash('sha256', $password);
        }

        return $password;
    }

    public static function is_user($person)
    {
        if (empty($person->guid)) {
            return false;
        }
        $qb = new midgard_query_builder('midgard_user');
        $qb->add_constraint('person', '=', $person->guid);
        return ($qb->count() > 0);
    }

    /**
     * Get current Midgard user
     *
     * @return int The current user ID
     */
    public static function get_user()
    {
        if ($user = midgard_connection::get_instance()->get_user()) {
            return $user->get_person()->id;
        }
        return 0;
    }

    /**
     * Check if the current user is admin
     *
     * @return boolean True or false
     */
    public static function is_admin()
    {
        if ($user = midgard_connection::get_instance()->get_user()) {
            return $user->is_admin();
        }
        return false;
    }

    /**
     * Getter for various environment-related variables.
     *
     * @param string $key The key to look up
     * @return mixed The found value or null
     */
    public static function get($key)
    {
        switch ($key) {
            case 'uri':
            case 'self':
            case 'prefix':
            case 'page_style':
            case 'argv':
                return self::get_url($key);
            default:
                return self::_get($key);
        }
    }

    private static function _get($key)
    {
        if (isset(self::$_data[$key])) {
            return self::$_data[$key];
        }

        return null;
    }

    /**
     * Lists all available MgdSchema types
     *
     * @return array A list of class names
     */
    public static function get_schema_types()
    {
        if (!isset(self::$_data['schema_types'])) {
            $classnames = connection::get_em()->getConfiguration()->getMetadataDriverImpl()->getAllClassNames();

            self::$_data['schema_types'] = array_filter($classnames, function($input) {
                return is_subclass_of($input, 'midgard_object');
            });
        }
        return self::$_data['schema_types'];
    }

    /**
     * Get various pieces of information extracted from the URL
     *
     * @return mixed The data for the requested key or false if it doesn't exist
     * @todo this should maybe check the key for validity
     */
    static function get_url($key)
    {
        static $parsed = false;
        if (!$parsed) {
            $url_components = parse_url("http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
            if (OPENPSA2_PREFIX !== '/') {
                $url_components['path'] = preg_replace('|^' . OPENPSA2_PREFIX . '|', '/', $url_components['path']);
            }
            self::_parse_url($url_components['path'], OPENPSA2_PREFIX, substr(OPENPSA2_PREFIX, 0, -1));
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
    private static function _parse_url($uri, $self, $prefix)
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
                 && midcom_helper_misc::check_page_exists($part)) {
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

    public static function get_unique_host_name()
    {
        if (null === self::_get('unique_host_name')) {
            self::$_data['unique_host_name'] = str_replace(':', '_', $_SERVER['SERVER_NAME']) . '_' . str_replace('/', '_', self::get_url('prefix'));
        }

        return self::$_data['unique_host_name'];
    }
}
