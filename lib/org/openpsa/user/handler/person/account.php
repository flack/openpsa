<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\workflow\delete;
use midcom\datamanager\datamanager;
use midcom\datamanager\controller;

/**
 * Account management class.
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_handler_person_account extends midcom_baseclasses_components_handler
{
    /**
     * The person we're working on
     *
     * @var midcom_db_person
     */
    private $person;

    /**
     * The account we're working on
     *
     * @var midcom_core_account
     */
    private $account;

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface');

        // Check if we get the person
        $this->person = new midcom_db_person($args[0]);
        $this->person->require_do('midgard:update');

        $this->account = new midcom_core_account($this->person);
        if ($this->account->get_username()) {
            throw new midcom_error('Given user already has an account');
        }

        midcom::get()->head->set_pagetitle($this->_l10n->get('create account'));

        $data['controller'] = $this->load_controller();

        $workflow = $this->get_workflow('datamanager', ['controller' => $data['controller']]);
        $response = $workflow->run();

        if (   $workflow->get_state() == 'save'
            && $this->_master->create_account($this->person, $data["controller"]->get_form_values())) {
            midcom::get()->uimessages->add($this->_l10n->get('org.openpsa.user'), sprintf($this->_l10n->get('person %s created'), $this->person->name));
        }

        return $response;
    }

    private function load_controller()
    {
        $schemadb = ($this->_request_data["handler_id"] == "account_edit") ? 'schemadb_account_edit' : 'schemadb_account';
        $defaults = [
            'person' => $this->person->guid
        ];
        if ($this->account->get_username()) {
            $defaults['username'] = $this->account->get_username();
        } elseif ($this->person->email) {
            // Email address (first part) is the default username
            $defaults['username'] = preg_replace('/@.*/', '', $this->person->email);
        } else {
            // Otherwise use cleaned up firstname.lastname
            $generator = midcom::get()->serviceloader->load('midcom_core_service_urlgenerator');
            $defaults['username'] = $generator->from_string($this->person->firstname) . '.' . $generator->from_string($this->person->lastname);
        }
        return datamanager::from_schemadb($this->_config->get($schemadb))
            ->set_defaults($defaults)
            ->get_controller();
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_su($handler_id, array $args, array &$data)
    {
        if (!midcom::get()->config->get('auth_allow_trusted')) {
            throw new midcom_error_forbidden('Trusted logins are disabled by configuration');
        }
        $this->person = new midcom_db_person($args[0]);
        $this->person->require_do($this->_component . ':su');

        $this->account = new midcom_core_account($this->person);
        if (!$username = $this->account->get_username()) {
            throw new midcom_error('Could not get username');
        }

        if (!midcom::get()->auth->trusted_login($username)) {
            throw new midcom_error('Login for user ' . $username . ' failed');
        }
        return new midcom_response_relocate(midcom_connection::get_url('self'));
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        if (!$this->load_person($args[0])) {
            // Account needs to be created first, relocate
            return new midcom_response_relocate("account/create/" . $this->person->guid . "/");
        }

        // if there is no password set (due to block), show ui-message for info
        $account_helper = new org_openpsa_user_accounthelper($this->person);
        if ($account_helper->is_blocked()) {
            midcom::get()->uimessages->add($this->_l10n->get('org.openpsa.user'), $this->_l10n->get("Account was blocked, since there is no password set."), 'error');
        }

        midcom::get()->head->set_pagetitle($this->_l10n->get('edit account'));

        $controller = $this->load_controller();

        $workflow = $this->get_workflow('datamanager', ['controller' => $controller]);

        if ($this->person->can_do('midgard:update')) {
            $delete = $this->get_workflow('delete', ['object' => $this->person]);
            $workflow->add_dialog_button($delete, "account/delete/{$this->person->guid}/");
        }

        $response = $workflow->run();
        if ($workflow->get_state() == 'save') {
            $this->update_account($controller);
        }
        return $response;
    }

    private function update_account(controller $controller)
    {
        $password = $controller->get_form_values()["new_password"] ?: null;
        $accounthelper = new org_openpsa_user_accounthelper($this->person);

        // Update account
        if (   !$accounthelper->set_account($controller->get_form_values()["username"], $password)
            && midcom_connection::get_error() != MGD_ERR_OK) {
            // Failure, give a message
            midcom::get()->uimessages->add($this->_l10n->get('org.openpsa.user'), $this->_l10n->get("failed to update the user account, reason") . ': ' . midcom_connection::get_error_string(), 'error');
        }
    }

    private function load_person($identifier)
    {
        $this->person = new midcom_db_person($identifier);
        $this->person->require_do('midgard:update');
        if ($this->person->id != midcom_connection::get_user()) {
            midcom::get()->auth->require_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface');
        }

        $this->account = new midcom_core_account($this->person);
        return (bool) $this->account->get_username();
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        if (!$this->load_person($args[0])) {
            // Account needs to be created first, relocate
            return new midcom_response_relocate("view/" . $this->person->guid . "/");
        }

        $workflow = new delete(['object' => $this->person]);
        if ($workflow->get_state() == delete::CONFIRMED) {
            if (!$this->account->delete()) {
                throw new midcom_error("Failed to delete account for {$this->person->guid}, last Midgard error was: " . midcom_connection::get_error_string());
            }
            midcom::get()->uimessages->add($this->_l10n->get($this->_component), sprintf($this->_l10n_midcom->get("%s deleted"), $this->_l10n->get('account')));
        }
        return new midcom_response_relocate('view/' . $this->person->guid . "/");
    }
}
