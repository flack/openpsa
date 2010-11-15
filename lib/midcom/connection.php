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
    static function get_error_string()
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