<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Interface class for user management
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_interface extends midcom_baseclasses_components_interface
{
    /**
     * Function to unblock an account after too many failed to login attempts
     *
     * @param array $args Contains the guid, parameter & parameter names to get username&password
     */
    function reopen_account($args, &$handler)
    {
        $_MIDCOM->auth->request_sudo($this->_component);
        try
        {
            $person = new midcom_db_person($args['guid']);
        }
        catch (midcom_error $e)
        {
            $msg = 'Person with guid #' . $args['guid'] . ' does not exist';
            debug_add($msg, MIDCOM_LOG_ERROR);
            $handler->print_error($msg);
            return false;
        }
        if (!empty($person->password))
        {
            $person->set_parameter($args['parameter_name'], $args['password'], "");
            $msg = 'Person with id #' . $person->id . ' does have a password so will not be set to the old one -- Account unblocked';
            debug_add($msg, MIDCOM_LOG_ERROR);
            $handler->print_error($msg);
            $_MIDCOM->auth->drop_sudo();
            return false;
        }

        $person->password = $person->get_parameter($args['parameter_name'], $args['password']);
        $person->set_parameter($args['parameter_name'], $args['password'], "");
        $person->update();
        $_MIDCOM->auth->drop_sudo();
        return true;
    }
}
?>