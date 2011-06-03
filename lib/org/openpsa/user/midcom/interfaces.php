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
        $accounthelper = new org_openpsa_user_accounthelper($person);
        try
        {
            $accounthelper->reopen_account();
            $_MIDCOM->auth->drop_sudo();
        }
        catch (midcom_error $e)
        {
            $_MIDCOM->auth->drop_sudo();
            $e->log();
            $handler->print_error($e->getMessage());
            return false;
        }
        return true;
    }
}
?>