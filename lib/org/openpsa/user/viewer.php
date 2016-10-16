<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Request class for user management
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_viewer extends midcom_baseclasses_components_request
{
    public function add_password_validation_code()
    {
        //get rules for js in style
        $this->_request_data['password_rules'] = $this->_config->get('password_score_rules');

        //get password_length & minimum score for js
        $this->_request_data['min_score'] = $this->_config->get('min_password_score');
        $this->_request_data['min_length'] = $this->_config->get('min_password_length');
        $this->_request_data['max_length'] = $this->_config->get('max_password_length');
    }

    /**
     * Create account based on data from DM2
     *
     * @param midcom_db_person $person The person we're working on
     * @param midcom_helper_datamanager2_formmanager $formmanager The formmanager instance to use
     */
    public function create_account(midcom_db_person $person, midcom_helper_datamanager2_formmanager $formmanager)
    {
        if (empty($formmanager->_types['username']))
        {
            return;
        }
        $account_helper = new org_openpsa_user_accounthelper();
        $formdata = $formmanager->get_submit_values();
        $password = "";

        //take user password?
        if ((int) $formdata['org_openpsa_user_person_account_password_switch'] > 0)
        {
            $password = $formmanager->_types['password']->value;
        }

        $stat = $account_helper->create_account
        (
            $person->guid,
            $formmanager->_types["username"]->value,
            $person->email,
            $password,
            $formmanager->_types["send_welcome_mail"]->value
        );
        if (!$stat)
        {
            midcom::get()->uimessages->add($this->_l10n->get($this->_component), $account_helper->errstr, 'error');
        }
        return $stat;
    }

    public function _on_handle($handler_id, array $args)
    {
        if ($handler_id != 'lostpassword')
        {
            midcom::get()->auth->require_valid_user();
        }
    }
}
