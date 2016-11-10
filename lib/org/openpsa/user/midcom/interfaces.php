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
     * @param array $args Contains the guid, parameter & parameter names to get username & password
     * @param midcom_baseclasses_components_cron_handler $handler cron_handler object calling this method.
     * @return boolean indicating success/failure
     */
    public function reopen_account(array $args, midcom_baseclasses_components_cron_handler $handler)
    {
        midcom::get()->auth->request_sudo($this->_component);
        try {
            $person = new midcom_db_person($args['guid']);
        } catch (midcom_error $e) {
            $handler->print_error('Person with guid #' . $args['guid'] . ' does not exist');
            midcom::get()->auth->drop_sudo();
            return false;
        }
        $accounthelper = new org_openpsa_user_accounthelper($person);
        try {
            $accounthelper->reopen_account();
        } catch (midcom_error $e) {
            $handler->print_error($e->getMessage());
            midcom::get()->auth->drop_sudo();
            return false;
        }
        midcom::get()->auth->drop_sudo();
        return true;
    }
}
