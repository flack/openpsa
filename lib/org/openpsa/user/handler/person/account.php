<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Account management class.
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_handler_person_account extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_nullstorage
{
    /**
     * The person we're working on
     *
     * @var midcom_db_person
     */
    private $_person;

    /**
     * The account we're working on
     *
     * @var midcom_core_account
     */
    private $_account;

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface');

        $data['controller'] = $this->get_controller('nullstorage');

        // Check if we get the person
        $this->_person = new midcom_db_person($args[0]);
        $this->_person->require_do('midgard:update');

        $this->_account = new midcom_core_account($this->_person);
        if ($this->_account->get_username())
        {
            throw new midcom_error('Given user already has an account');
        }

        switch ($data['controller']->process_form())
        {
            case 'save':
                $this->_master->create_account($this->_person, $data["controller"]->formmanager);

            case 'cancel':
                return new midcom_response_relocate('view/' . $this->_person->guid . '/');
        }

        if ($this->_person->email)
        {
            // Email address (first part) is the default username
            $this->_request_data['default_username'] = preg_replace('/@.*/', '', $this->_person->email);
        }
        else
        {
            // Otherwise use cleaned up firstname.lastname
            $generator = midcom::get('serviceloader')->load('midcom_core_service_urlgenerator');
            $this->_request_data['default_username'] = $generator->from_string($this->_person->firstname) . '.' . $generator->from_string($this->_person->lastname);
        }

        midcom::get('head')->set_pagetitle("{$this->_person->firstname} {$this->_person->lastname}");
        $this->_prepare_request_data();

        $this->_update_breadcrumb_line('create account');

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);
    }

    public function load_schemadb()
    {
        $handler = $this->_request_data["handler_id"];

        //account edit
        if ($handler == "account_edit")
        {
            $schemadb_config_string = "schemadb_account_edit";

            $db = midcom_helper_datamanager2_schema::load_database($this->_config->get($schemadb_config_string));

            //set defaults
            $db["default"]->fields["username"]["default"] = $this->_account->get_username();
            $db["default"]->fields["person"]["default"] = $this->_person->guid;
        }
        //account create
        else
        {
            $schemadb_config_string = "schemadb_account";
            $db = midcom_helper_datamanager2_schema::load_database($this->_config->get($schemadb_config_string));
        }

        return $db;
    }

    private function _prepare_request_data()
    {
        $this->_request_data['person'] = $this->_person;
        $this->_request_data['account'] = $this->_account;
        $this->_master->add_password_validation_code();
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_su($handler_id, array $args, array &$data)
    {
        if (!midcom::get('config')->get('auth_allow_trusted'))
        {
            throw new midcom_error_forbidden('Trusted logins are disabled by configuration');
        }
        $this->_person = new midcom_db_person($args[0]);
        $this->_person->require_do($this->_component . ':su');

        $this->_account = new midcom_core_account($this->_person);
        if (!$username = $this->_account->get_username())
        {
            throw new midcom_error('Could not get username');
        }

        if (!midcom::get('auth')->trusted_login($username))
        {
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
        $this->_person = new midcom_db_person($args[0]);
        $this->_person->require_do('midgard:update');
        if ($this->_person->id != midcom_connection::get_user())
        {
            midcom::get('auth')->require_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface');
        }

        //get existing account for gui
        $this->_account = new midcom_core_account($this->_person);
        //if we have no username there is no account
        if (!$this->_account->get_username())
        {
            // Account needs to be created first, relocate
            return new midcom_response_relocate("account/create/" . $this->_person->guid . "/");
        }

        // if there is no password set (due to block), show ui-message for info
        $midcom_person = midcom_db_person::get_cached($this->_person->id);
        $account_helper = new org_openpsa_user_accounthelper($midcom_person);
        if ($account_helper->is_blocked())
        {
            midcom::get('uimessages')->add($this->_l10n->get('org.openpsa.user'), $this->_l10n->get("Account was blocked, since there is no password set."), 'error');
        }
        $data['controller'] = $this->get_controller('nullstorage');
        $formmanager = $data["controller"]->formmanager;

        switch ($data['controller']->process_form())
        {
            case 'save':
                if (!$this->_update_account($formmanager->_types))
                {
                    break;
                }
                //Fall-through

            case 'cancel':
                return new midcom_response_relocate("view/" . $this->_person->guid . "/");
        }

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/midcom.helper.datamanager2/legacy.css");
        midcom::get('head')->enable_jquery();
        midcom::get('head')->set_pagetitle("{$this->_person->firstname} {$this->_person->lastname}");
        $this->_prepare_request_data();

        $this->_update_breadcrumb_line('edit account');

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "account/delete/{$this->_person->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('delete account'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/properties.png',
            )
        );
    }

    private function _update_account($fields)
    {
        $stat = false;

        $password = null;
        //new password?
        if (!empty($fields["new_password"]->value))
        {
            $password = $fields["new_password"]->value;
        }
        $accounthelper = new org_openpsa_user_accounthelper($this->_person);

        // Update account
        $stat = $accounthelper->set_account($fields["username"]->value, $password);
        if (   !$stat
            && midcom_connection::get_error() != MGD_ERR_OK)
        {
            // Failure, give a message
            midcom::get('uimessages')->add($this->_l10n->get('org.openpsa.user'), $this->_l10n->get("failed to update the user account, reason") . ': ' . midcom_connection::get_error_string(), 'error');
        }

        return $stat;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        // Check if we get the person
        $this->_person = new midcom_db_person($args[0]);
        $this->_person->require_do('midgard:update');
        if ($this->_person->id != midcom_connection::get_user())
        {
            midcom::get('auth')->require_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface');
        }

        $this->_account = new midcom_core_account($this->_person);
        if (!$this->_account->get_username())
        {
            // Account needs to be created first, relocate
            return new midcom_response_relocate("view/" . $this->_person->guid . "/");
        }

        $data['controller'] = midcom_helper_datamanager2_handler::get_delete_controller();

        switch ($data['controller']->process_form())
        {
            case 'delete':
                if (!$this->_account->delete())
                {
                    throw new midcom_error("Failed to delete account for {$this->_person->guid}, last Midgard error was: " . midcom_connection::get_error_string());
                }
                //Fall-through
            case 'cancel':
                return new midcom_response_relocate('view/' . $this->_person->guid . "/");
        }

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/midcom.helper.datamanager2/legacy.css");
        midcom::get('head')->enable_jquery();
        midcom::get('head')->set_pagetitle("{$this->_person->firstname} {$this->_person->lastname}");
        $this->_prepare_request_data();

        $this->_update_breadcrumb_line('delete account');

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this, 'delete');
    }

    /**
     * Helper, updates the context so that we get a complete breadcrumb line towards the current
     * location.
     */
    private function _update_breadcrumb_line($action)
    {
        $this->add_breadcrumb("view/{$this->_person->guid}/", $this->_person->name);
        $this->add_breadcrumb("", $this->_l10n->get($action));
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_create($handler_id, array &$data)
    {
        midcom_show_style("show-person-account-create");
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_edit($handler_id, array &$data)
    {
        midcom_show_style("show-person-account-edit");
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_delete($handler_id, array &$data)
    {
        midcom_show_style("show-person-account-delete");
    }
}
?>