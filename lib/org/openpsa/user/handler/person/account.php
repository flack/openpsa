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
use Symfony\Component\HttpFoundation\Request;

/**
 * Account management class.
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_handler_person_account extends midcom_baseclasses_components_handler
{
    use org_openpsa_user_handler;

    private midcom_db_person $person;

    private midcom_core_account $account;

    public function _handler_create(Request $request, string $guid, array &$data)
    {
        midcom::get()->auth->require_user_do('org.openpsa.user:manage', class: org_openpsa_user_interface::class);

        // Check if we get the person
        $this->person = new midcom_db_person($guid);
        $this->person->require_do('midgard:update');

        $this->account = new midcom_core_account($this->person);
        if ($this->account->get_username()) {
            throw new midcom_error('Given user already has an account');
        }

        midcom::get()->head->set_pagetitle($this->_l10n->get('create account'));

        $data['controller'] = $this->load_controller();

        $workflow = $this->get_workflow('datamanager', ['controller' => $data['controller']]);
        $response = $workflow->run($request);

        if (   $workflow->get_state() == 'save'
            && $this->create_account($this->person, $data["controller"]->get_form_values())) {
            midcom::get()->uimessages->add($this->_l10n->get('org.openpsa.user'), sprintf($this->_l10n->get('person %s created'), $this->person->name));
        }

        return $response;
    }

    private function load_controller() : controller
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
            $defaults['username'] = midcom_helper_misc::urlize($this->person->firstname) . '.' . midcom_helper_misc::urlize($this->person->lastname);
        }
        return datamanager::from_schemadb($this->_config->get($schemadb))
            ->set_defaults($defaults)
            ->get_controller();
    }

    public function _handler_welcome_email(string $guid)
    {
        $person = new midcom_db_person($guid);
        $accounthelper = new org_openpsa_user_accounthelper($person);
        if ($accounthelper->welcome_email()) {
            midcom::get()->uimessages->add($this->_l10n->get($this->_component), sprintf($this->_l10n->get('password reset and mail to %s sent'), $person->email ));
        } else {
            midcom::get()->uimessages->add($this->_l10n->get($this->_component), $accounthelper->errstr, 'error');
        }
        return new midcom_response_relocate($this->router->generate('user_view', ['guid' => $guid]));
    }

    public function _handler_su(string $guid)
    {
        if (!midcom::get()->config->get('auth_allow_trusted')) {
            throw new midcom_error_forbidden('Trusted logins are disabled by configuration');
        }
        $this->person = new midcom_db_person($guid);
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

    public function _handler_edit(Request $request, string $guid)
    {
        if (!$this->load_person($guid)) {
            // Account needs to be created first, relocate
            return new midcom_response_relocate($this->router->generate('account_create', ['guid' => $guid]));
        }

        midcom::get()->head->set_pagetitle($this->_l10n->get('edit account'));
        org_openpsa_user_widget_password::jsinit('input[name="org_openpsa_user[new_password][first]"]', $this->_l10n, $this->_config, true);
        $controller = $this->load_controller();
        $workflow = $this->get_workflow('datamanager', ['controller' => $controller]);

        if ($this->person->can_do('midgard:update')) {
            $delete = $this->get_workflow('delete', ['object' => $this->person]);
            $workflow->add_dialog_button($delete, $this->router->generate('account_delete', ['guid' => $guid]));
        }

        $response = $workflow->run($request);
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
            midcom::get()->uimessages->add($this->_l10n->get('org.openpsa.user'), $this->_l10n->get("failed to update the user account, reason") . ': ' . $accounthelper->errstr, 'error');
        }
    }

    private function load_person(string $identifier) : bool
    {
        $this->person = new midcom_db_person($identifier);
        $this->person->require_do('midgard:update');
        if ($this->person->guid != midcom::get()->auth->user->guid) {
            midcom::get()->auth->require_user_do('org.openpsa.user:manage', class: org_openpsa_user_interface::class);
        }

        $this->account = new midcom_core_account($this->person);
        return (bool) $this->account->get_username();
    }

    public function _handler_delete(Request $request, string $guid)
    {
        if (!$this->load_person($guid)) {
            // Account needs to be created first, relocate
            return new midcom_response_relocate($this->router->generate('user_view', ['guid' => $guid]));
        }

        $workflow = new delete(['object' => $this->person]);
        if ($workflow->is_confirmed($request)) {
            if (!$this->account->delete()) {
                throw new midcom_error("Failed to delete account for {$this->person->guid}, last Midgard error was: " . midcom_connection::get_error_string());
            }
            midcom::get()->uimessages->add($this->_l10n->get($this->_component), sprintf($this->_l10n_midcom->get("%s deleted"), $this->_l10n->get('account')));
        }
        return new midcom_response_relocate($this->router->generate('user_view', ['guid' => $guid]));
    }
}
