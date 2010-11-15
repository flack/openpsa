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
}
?>