<?php
/**
 * @package midcom
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Wrapper for Midgard-related functionality, provides compatibility between versions
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
    private static $_data;

    private static $_defaults = array
    (
        'argv' => array(),

        'user' => 0,
        'admin' => false,
        'root' => false,

        'auth' => false,
        'cookieauth' => false,

        // General host setup
        'page' => 0,
        'debug' => false,

        'host' => 0,
        'style' => 0,
        'author' => 0,
        'config' => array
        (
            'prefix' => '',
            'quota' => false,
            'unique_host_name' => 'openpsa',
            'auth_cookie_id' => 1,
        ),

        'schema' => array
        (
        ),
    );

    /**
     * Check whether Midgard database connection exists
     *
     * @return boolean
     */
    static function is_connected()
    {
        if (method_exists('midgard_connection', 'get_instance'))
        {
            // Midgard 9.09 or newer
            return midgard_connection::get_instance()->is_connected();
        }
        // Midgard 8.09 or 9.03
        return true;
    }

    /**
     * Set Midgard log level
     *
     * @param string $loglevel Midgard log level
     */
    static function set_loglevel($loglevel)
    {
        if (method_exists('midgard_connection', 'get_instance'))
        {
            // Midgard 9.09 or newer
            return midgard_connection::get_instance()->set_loglevel($loglevel);
        }
        // Midgard 8.09 or 9.03
        return midgard_connection::set_loglevel($loglevel);
    }

    /**
     * Set Midgard error code
     *
     * @param int $errorcode Midgard error code
     */
    static function set_error($errorcode)
    {
        if (method_exists('midgard_connection', 'get_instance'))
        {
            // Midgard 9.09 or newer
            return midgard_connection::get_instance()->set_error($errorcode);
        }
        // Midgard 8.09 or 9.03
        return midgard_connection::set_error($errorcode);
    }

    /**
     * Get Midgard error code
     *
     * @return int Midgard error code
     */
    static function get_error()
    {
        if (method_exists('midgard_connection', 'get_instance'))
        {
            // Midgard 9.09 or newer
            return midgard_connection::get_instance()->get_error();
        }
        // Midgard 8.09 or 9.03
        return midgard_connection::get_error();
    }

    /**
     * Get Midgard error message
     *
     * @return string Midgard error message
     */
    public static function get_error_string()
    {
        if (method_exists('midgard_connection', 'get_instance'))
        {
            // Midgard 9.09 or newer
            return midgard_connection::get_instance()->get_error_string();
        }
        // Midgard 8.09 or 9.03
        return midgard_connection::get_error_string();
    }

    /**
     * Perform a login against the midgard backend
     *
     * @param string $username The username as entered
     * @param string $password The password as entered
     * @param boolean $trusted Use trusted auth (mgd1 only, ATM)
     * @return mixed The appropriate object or false
     */
    public static function login($username, $password, $trusted = false)
    {
        if (method_exists('midgard_user', 'login'))
        {
            // Ratatoskr
            $login_tokens = array
            (
                'login' => $username,
                'authtype' => $GLOBALS['midcom_config']['auth_type'],
            );

            if (!$trusted)
            {
                $login_tokens['password'] = self::prepare_password($password);
            }

            try
            {
                $user = new midgard_user($login_tokens);
            }
            catch (midgard_error_exception $e)
            {
                return false;
            }

            if (!$user->login())
            {
                return false;
            }
            return $user;
        }
        else
        {
            // Ragnaroek
            $sg_name = '';
            $mode = $GLOBALS['midcom_config']['auth_sitegroup_mode'];

            if ($mode == 'auto')
            {
                $mode = (self::_get('sitegroup') == 0) ? 'not-sitegrouped' : 'sitegrouped';
            }

            if ($mode == 'sitegrouped')
            {
                $sitegroup = new midgard_sitegroup(self::_get('sitegroup'));
                $sg_name = $sitegroup->name;
            }
            $stat = midgard_user::auth($username, $password, $sg_name, $trusted);
            if (   !$stat
                && $GLOBALS['midcom_config']['auth_type'] == 'Plaintext'
                && strlen($password) > 11)
            {
                //mgd1 has the password field defined with length 13, but it doesn't complain
                //when saving a longer password, it just sometimes shortens it, so we try the
                //shortened version here (we cut at 11 because the first two characters are **)
                $stat = midgard_user::auth($username, substr($password, 0, 11), $sg_name, $trusted);
            }
            return $stat;
        }
    }

    public static function prepare_password($password)
    {
        if (method_exists('midgard_user', 'login'))
        {
            switch ($GLOBALS['midcom_config']['auth_type'])
            {
                case 'Plaintext':
                    // Compare plaintext to plaintext
                    break;
                case 'Legacy':
                    // Midgard1 legacy auth
                    $salt = ''; //TODO: How to determine the correct one?
                    $password = crypt($password, $salt);
                    break;
                case 'SHA1':
                    $password = sha1($password);
                    break;
                case 'SHA256':
                    $password = hash('sha256', $password);
                    break;
                case 'MD5':
                    $password = md5($password);
                    break;
                default:
                    throw new midcom_error('Unsupported authentication type attempted', 500);
            }
            // TODO: Support other types
        }
        else
        {
            switch ($GLOBALS['midcom_config']['auth_type'])
            {
                case 'Plaintext':
                    //do not add the ** for empty passwords - in case it was set to empty do disable account
                    if (!empty($password))
                    {
                        $password = '**' . $password;
                    }
                    break;
                case 'Legacy':
                    /*
                      It seems having nonprintable characters in the password breaks replication
                      Here we recreate salt and hash until we have a combination where only
                      printable characters exist
                    */
                    $crypted = false;
                    while (   empty($crypted)
                           || preg_match('/[\x00-\x20\x7f-\xff]/', $crypted))
                    {
                        $salt = chr(rand(33, 125)) . chr(rand(33, 125));
                        $crypted = crypt($password, $salt);
                    }
                    $password = $crypted;
                    unset($crypted);
                    break;
                default:
                    throw new midcom_error('Unsupported authentication type attempted', 500);
            }
        }

        return $password;
    }

    public static function is_user($person)
    {
        if (empty($person->guid))
        {
            return false;
        }
        if (method_exists('midgard_user', 'login'))
        {
            // Ratatoskr
            $qb = new midgard_query_builder('midgard_user');
            $qb->add_constraint('person', '=', $person->guid);
            return ($qb->count() > 0);
        }
        else
        {
            // Ragnaroek
            return ($person->username != '');
        }
    }

    /**
     * Get current Midgard user
     *
     * @return int The current user ID
     */
    public static function get_user()
    {
        if (method_exists('midgard_connection', 'get_instance'))
        {
            // Midgard 9.09 or newer
            $user = midgard_connection::get_instance()->get_user();
        }
        else
        {
            // Midgard 8.09 or 9.03
            $user = midgard_connection::get_user();
        }

        if (!$user)
        {
            return 0;
        }

        $person = $user->get_person();
        return $person->id;
    }

    /**
     * Check if the current user is admin
     *
     * @return boolean True or false
     */
    static function is_admin()
    {
        if (method_exists('midgard_connection', 'get_instance'))
        {
            // Midgard 9.09 or newer
            $user = midgard_connection::get_instance()->get_user();
        }
        else
        {
            // Midgard 8.09 or 9.03
            $user = midgard_connection::get_user();
        }

        if (!$user)
        {
            return false;
        }

        return $user->is_admin();
    }

    /**
     * Getter for various environment-related variables. this serves mostly as a drop-in
     * replacement for $_MIDGARD superglobal access
     *
     * @param string $key The key to look up
     * @param string $subkey The subkey, if any
     * @return mixed The found value or null
     */
    public static function get($key, $subkey = null)
    {
        switch ($key)
        {
            case 'uri':
            case 'self':
            case 'prefix':
            case 'page_style':
            case 'argv':
            case 'argc':
                return self::get_url($key);
            case 'schema':
                if ($subkey == 'types')
                {
                    return self::get_schema_types();
                }
            case 'config':
                if ($subkey == 'unique_host_name')
                {
                    return self::get_unique_host_name();
                }
            default:
                return self::_get($key, $subkey);
        }
    }

    public static function _get($key, $subkey = null)
    {
        if (null === self::$_data)
        {
            if (!empty($_MIDGARD))
            {
                self::$_data = $_MIDGARD;
            }
            else
            {
                self::$_data = self::$_defaults;
            }
        }

        if (   null === $subkey
            && isset(self::$_data[$key]))
        {
            return self::$_data[$key];
        }
        if (   null !== $subkey
            && isset(self::$_data[$key][$subkey]))
        {
            return self::$_data[$key][$subkey];
        }

        return null;
    }

    /**
     * Lists all available MgdSchema types
     *
     * @return array A list of class names
     */
    static function get_schema_types()
    {
        if (isset(self::$_data['schema_types']))
        {
            return self::$_data['schema_types'];
        }
        if (null === self::_get('schema', 'types'))
        {
            // Superglobal is off, Midgard 9.09 or newer
            // Get the classes from PHP5 reflection
            $re = new ReflectionExtension('midgard2');
            $classes = $re->getClasses();
            foreach ($classes as $refclass)
            {
                if ($refclass->isSubclassOf('midgard_object'))
                {
                    $name = $refclass->getName();
                    if (   class_exists('MidgardReflectorObject')
                        && (   MidgardReflectorObject::is_abstract($name)
                            || MidgardReflectorObject::is_mixin($name)
                            || MidgardReflectorObject::is_interface($name)))
                    {
                        continue;
                    }
                    self::$_data['schema_types'][] = $name;
                }
            }
        }
        else
        {
            // Midgard 8.09 or 9.03
            self::$_data['schema_types'] = array_keys(self::_get('schema', 'types'));
        }

        return self::$_data['schema_types'];
    }

    /**
     * Get various pieces of information extracted from the URL
     *
     * @return mixed The data for the requested key or false if it doesn't exist
     * @todo this should mabe check the key for validity
     */
    static function get_url($key)
    {
        static $parsed = false;
        if (!$parsed)
        {
            // This has the side effect to ensure that $_data is properly initialized
            if (null !== self::_get($key))
            {
                // key was found, so we must have a (real, mgd1) superglobal
                self::_parse_url(implode('/', $_MIDGARD['argv']), $_MIDGARD['self'], $_MIDGARD['prefix']);
            }
            else
            {
                // Superglobal disabled, Midgard 9.09 or newer
                $url_components = parse_url("http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
                self::_parse_url($url_components['path'], OPENPSA2_PREFIX, substr(OPENPSA2_PREFIX, 0, -1));
            }
            $parsed = true;
        }

        return self::_get($key);
    }

    /**
     * Helper function that enables themes to have subdirectories (which have a similar effect to mgd1 pages)
     *
     * @param string $uri The request path
     * @param string $self The instance's root URL
     * @param string $prefix The root URL's prefix, if any (corresponds to mgd1 host)
     */
    private static function _parse_url($uri, $self, $prefix)
    {
        $path_parts = explode('/', $uri);
        $page_style = '';
        $path = $self;

        self::$_data['argv'] = array();
        $args_started = false;
        foreach ($path_parts as $part)
        {
            if ($part == '')
            {
                continue;
            }

            if (    isset($GLOBALS['midcom_config']['theme'])
                 && !$args_started
                 && is_dir(OPENPSA2_THEME_ROOT . $GLOBALS['midcom_config']['theme'] . '/style/' . $part))
            {
                $page_style .= '/' . $part;
                $self .= $part . '/';
            }
            else
            {
                self::$_data['argv'][] = $part;
                $path .= $part . '/';
                $args_started = true;
            }
        }

        self::$_data['page_style'] = $page_style;
        self::$_data['uri'] = $path;
        self::$_data['argc'] = count(self::$_data['argv']);
        self::$_data['self'] = $self;
        self::$_data['prefix'] = $prefix;
    }

    public static function get_unique_host_name()
    {
        if (null === self::_get('config', 'unique_host_name'))
        {
            self::$_data['config']['unique_host_name'] = str_replace(':', '_', $_SERVER['SERVER_NAME']) . '_' . str_replace('/', '_', midcom_connection::get_url('prefix'));
        }

        return self::$_data['config']['unique_host_name'];
    }
}
?>