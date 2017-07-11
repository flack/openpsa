<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\storage\container\container;

/**
 * Request class for user management
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_viewer extends midcom_baseclasses_components_request
{
    /**
     * Create account based on data from DM2
     *
     * @param midcom_db_person $person The person we're working on
     * @param container $formdata The form data
     */
    public function create_account(midcom_db_person $person, container $formdata)
    {
        if (empty($formdata['username'])) {
            return;
        }
        $account_helper = new org_openpsa_user_accounthelper();
        $password = "";

        //take user password?
        if ((int) $formdata['password']['switch'] > 0) {
            $password = $formdata['password']['password'];
        }

        $stat = $account_helper->create_account(
            $person->guid,
            $formdata['username'],
            $person->email,
            $password,
            $formdata['send_welcome_mail']
        );
        if (!$stat) {
            midcom::get()->uimessages->add($this->_l10n->get($this->_component), $account_helper->errstr, 'error');
        }

        return $stat;
    }

    public function _on_handle($handler_id, array $args)
    {
        if ($handler_id != 'lostpassword') {
            midcom::get()->auth->require_valid_user();
        }
    }
}
