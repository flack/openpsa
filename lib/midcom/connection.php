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
    private static $_data = array();

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
                'authtype' => $GLOBALS['midcom_config']['auth_type']
            );

            $user = midgard_user::get($login_tokens);
            if (is_null($user))
            {
                //the account apparently has not yet been migrated. Do this now
                if (!self::_migrate_account($username))
                {
                    return false;
                }
            }

            $login_tokens['password'] = self::_prepare_midgard2_password($password);

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
                $mode = ($_MIDGARD['sitegroup'] == 0) ? 'not-sitegrouped' : 'sitegrouped';
            }

            if ($mode == 'sitegrouped')
            {
                $sitegroup = new midgard_sitegroup($_MIDGARD['sitegroup']);
                $sg_name = $sitegroup->name;
            }
            return midgard_user::auth($username, $password, $sg_name, $trusted);
        }
    }

    private static function _prepare_midgard2_password($password)
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

        return $password;
    }

    private static function _migrate_account($username)
    {
        $qb = new midgard_query_builder($GLOBALS['midcom_config']['person_class']);
        $qb->add_constraint('username', '=', $username);
        $results = $qb->execute();
        if (sizeof($results) != 1)
        {
            return false;
        }

        $person = $results[0];
        $user = new midgard_user();
        $db_password = $person->password;

        if (substr($person->password, 0, 2) == '**')
        {
            $db_password = substr($db_password, 2);
        }
        else
        {
            debug_add('Legacy password detected for person ' . $person->id . '. Resetting to "password", please change ASAP ', MIDCOM_LOG_ERROR);
            $db_password = 'password';
        }
        $user->authtype = $GLOBALS['midcom_config']['auth_type'];

        $user->password = self::_prepare_midgard2_password($db_password);
        $user->login = $person->username;

        if ($GLOBALS['midcom_config']['person_class'] != 'midgard_person')
        {
            $mgd_person = new midgard_person($person->guid);
        }
        else
        {
            $mgd_person = $person;
        }

        $user->set_person($mgd_person);

        try
        {
            $user->create();
        }
        catch (midgard_error_exception $e)
        {
            return false;
        }
        return true;
    }

    /**
     * Get current Midgard user
     *
     * @return int The current user ID
     */
    static function get_user()
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
        if (method_exists('midgard_connection', 'get_instance'))
        {
            // Midgard 9.09 or newer
            // Get the classes from PHP5 reflection
            $re = new ReflectionExtension('midgard2');
            $classes = $re->getClasses();
            foreach ($classes as $refclass)
            {
                $parent_class = $refclass->getParentClass();
                if (!$parent_class)
                {
                    continue;
                }
                if ($parent_class->getName() == 'midgard_object')
                {
                    self::$_data['schema_types'][] = $refclass->getName();
                }
            }
        }
        else
        {
            // Midgard 8.09 or 9.03
            self::$_data['schema_types'] = array_keys($_MIDGARD['schema']['types']);
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
        if (method_exists('midgard_connection', 'get_instance'))
        {
            // Midgard 9.09 or newer
            if (!array_key_exists($key, self::$_data))
            {
                self::_parse_url();
            }

            if (array_key_exists($key, self::$_data))
            {
                return self::$_data[$key];
            }
        }
        else if (array_key_exists($key, $_MIDGARD))
        {
            // Midgard 8.09 or 9.03
            return $_MIDGARD[$key];
        }

        return false;
    }

    /**
     * Helper function that emulates Midgard1 URL parsing. It is pretty basic at this point,
     * f.x. it doesn't know about host prefixes and pages.
     */
    private static function _parse_url()
    {
        $url_components = parse_url("http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");

        $path = '/';
        $path_parts = explode('/', $url_components['path']);

        self::$_data['argv'] = array();

        $args_started = false;
        foreach ($path_parts as $part)
        {
            if ($part == '')
            {
                continue;
            }
            // @todo Port the theme part to midgard1
            if (    isset($_MIDGARD['theme'])
                 && !$args_started
                 && is_dir(OPENPSA2_THEME_ROOT . $_MIDGARD['theme'] . '/' . $part))
            {
                $_MIDGARD['page_style'] .= '/' . $part;
            }
            else
            {
                self::$_data['argv'][] = $part;
                $path .= $part . '/';
                $args_started = true;
            }
        }

        self::$_data['uri'] = $path;
        // @todo This should be smarter
        self::$_data['self'] = '/';
        self::$_data['prefix'] = substr(self::$_data['self'], 0, -1);

        self::$_data['argc'] = count(self::$_data['argv']);
    }
}
?>