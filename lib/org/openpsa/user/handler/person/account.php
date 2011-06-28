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
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface');

        $data['controller'] = $this->get_controller('nullstorage');

        // Check if we get the person
        $this->_person = new midcom_db_person($args[0]);

        midcom::get('auth')->require_do('midgard:update', $this->_person);

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
                $_MIDCOM->relocate('view/' . $this->_person->guid . '/');
        }

        if ($this->_person->email)
        {
            // Email address (first part) is the default username
            $this->_request_data['default_username'] = preg_replace('/@.*/', '', $this->_person->email);
        }
        else
        {
            // Otherwise use cleaned up firstname.lastname
            $this->_request_data['default_username'] = midcom_helper_misc::generate_urlname_from_string($this->_person->firstname) . '.' . midcom_helper_misc::generate_urlname_from_string($this->_person->lastname);
        }

        $_MIDCOM->set_pagetitle("{$this->_person->firstname} {$this->_person->lastname}");
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

    private function _generate_password()
    {
        $this->_request_data["default_password"] = self::generate_password($this->_config->get('default_password_length'));
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->_person = new midcom_db_person($args[0]);

        midcom::get('auth')->require_do('midgard:update', $this->_person);
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
            $_MIDCOM->relocate("account/create/" . $this->_person->guid . "/");
        }
        //if there is no pasword set, show ui-message for info
        if (!$this->_account->get_password())
        {
            $_MIDCOM->uimessages->add($this->_l10n->get('org.openpsa.user'), $this->_l10n->get("Account was blocked, since there is no password set."), 'error');
        }
        $data['controller'] = $this->get_controller('nullstorage');
        $formmanager = $data["controller"]->formmanager;

        switch ($data['controller']->process_form())
        {
            case 'save':
                if (!$this->_update_account($formmanager->_types))
                {
                    $_MIDCOM->relocate("account/edit/" . $this->_person->guid . "/");
                }
                //Fall-through

            case 'cancel':
                $_MIDCOM->relocate("view/" . $this->_person->guid . "/");
        }

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/midcom.helper.datamanager2/legacy.css");
        $_MIDCOM->enable_jquery();
        $_MIDCOM->set_pagetitle("{$this->_person->firstname} {$this->_person->lastname}");
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
                MIDCOM_TOOLBAR_ENABLED => midcom::get('auth')->can_do('midgard:update', $this->_person),
            )
        );
    }

    private function _update_account($fields)
    {
        $stat = false;

        //check user auth if current user is not admin
        if (!midcom::get('auth')->can_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface'))
        {
            //user auth
            $check_user = midcom_connection::login($this->_account->get_username(), $fields["current_password"]->value);
        }
        else
        {
            $check_user = true;
        }

        if (!$check_user)
        {
            $_MIDCOM->uimessages->add($this->_l10n->get('org.openpsa.user'), $this->_l10n->get("wrong current password"), 'error');
        }
        //auth ok
        else
        {
            $password = null;
            //new password?
            if (!empty($fields["new_password"]->value))
            {
                $password = $fields["new_password"]->value;
            }
            $accounthelper = new org_openpsa_user_accounthelper($this->_person);

            // Update account
            $stat = $accounthelper->set_account($fields["username"]->value, $password);
            if (!$stat)
            {
                // Failure, give a message
                $_MIDCOM->uimessages->add($this->_l10n->get('org.openpsa.user'), $this->_l10n->get("failed to update the user account, reason") . ': ' . midcom_connection::get_error_string(), 'error');
            }
        }

        return $stat;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        // Check if we get the person
        $this->_person = new midcom_db_person($args[0]);
        midcom::get('auth')->require_do('midgard:update', $this->_person);

        if ($this->_person->id != midcom_connection::get_user())
        {
            midcom::get('auth')->require_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface');
        }
        $this->_account = new midcom_core_account($this->_person);
        if (!$this->_account->get_username())
        {
            // Account needs to be created first, relocate
            $_MIDCOM->relocate("view/" . $this->_person->guid . "/");
        }

        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
        if (   array_key_exists('midcom_helper_datamanager2_save', $_POST)
            && $this->_account->delete())
        {
            // Account updated, redirect to person card
            $_MIDCOM->relocate('view/' . $this->_person->guid . "/");
        }
        else if (array_key_exists('midcom_helper_datamanager2_cancel', $_POST))
        {
            $_MIDCOM->relocate('view/' . $this->_person->guid . "/");
        }

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/midcom.helper.datamanager2/legacy.css");
        $_MIDCOM->enable_jquery();
        $_MIDCOM->set_pagetitle("{$this->_person->firstname} {$this->_person->lastname}");
        $this->_prepare_request_data();

        $this->_update_breadcrumb_line('delete account');

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);
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