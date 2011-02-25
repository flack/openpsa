<?php

/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.contacts account handler and viewer class.
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_handler_person_account extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_nullstorage
{
    /**
     * The person we're working with, if any
     *
     * @var org_openpsa_contacts_person_dba
     */
    private $_person;

    /**
     * The account we're working with, if any
     *
     * @var midcom_core_account
     */
    private $_account;

    public function _on_initialize()
    {
        $_MIDCOM->auth->require_valid_user();
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


    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_account_create($handler_id, $args, &$data)
    {
        $data['controller'] = $this->get_controller('nullstorage');
        $formmanager = $data["controller"]->formmanager;

        // Check if we get the person
        $this->_person = new org_openpsa_contacts_person_dba($args[0]);
        $_MIDCOM->auth->require_do('midgard:update', $this->_person);

        $this->_account = new midcom_core_account($this->_person);
        if ($this->_account->get_username())
        {
            throw new midcom_error('Creating new account for existing account is not possible');
        }

        switch ($data['controller']->process_form())
        {
            case 'save':
                $account_helper = new org_openpsa_contacts_accounthelper();

                $password = "";
                //take user password?
                if(intval($_POST['org_openpsa_contacts_person_account_password_switch']) > 0){
                    $password = $_POST['org_openpsa_contacts_person_account_password'];
                }

                $account_helper->create_account(
                    $args[0], //guid
                    $formmanager->_types["username"]->value, //username
                    $this->_person->email, //usermail
                    $password, //password
                    $formmanager->_types["send_welcome_mail"]->value //send welcome mail
                );

            case 'cancel':
                $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
                $_MIDCOM->relocate($prefix . "person/" . $this->_person->guid . "/");
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

        //$this->_generate_password();

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/midcom.helper.datamanager2/legacy.css");
        $_MIDCOM->set_pagetitle("{$this->_person->firstname} {$this->_person->lastname}");
        $this->_prepare_request_data();

        $this->_update_breadcrumb_line('create account');

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);
    }

    private function _prepare_request_data()
    {
        $this->_request_data['person'] =& $this->_person;
        $this->_request_data['account'] =& $this->_account;

        //get rules for js in style
        $rules = $this->_config->get('password_match_score');
        $data_rules = midcom_helper_misc::get_snippet_content($rules);
        $result = eval ("\$contents = array ( {$data_rules}\n );");
        if ($result === false)
        {
            throw new midcom_error("Failed to parse the schema definition in '{$rules}', see above for PHP errors.");
        }
        $this->_request_data['password_rules'] = $contents['rules'];

        //get password_length & minimum score for js
        $this->_request_data['min_score'] = $this->_config->get('min_password_score');
        $this->_request_data['min_length'] = $this->_config->get('min_password_length');
        $this->_request_data['max_length'] = $this->_config->get('max_password_length');
    }

    /**
     * returns an auto generated password of variable length
     * @param int length: the number of chars the password will contain
     * @return string password: the generated password
     */
    public static function generate_password($length=0){
        // We should do this by listing to /dev/urandom
        // Safety
        if ($length == 0)
        {
            $length = 8;
        }
        if (function_exists('mt_rand'))
        {
            $rand = 'mt_rand';
        }
        else
        {
            $rand = 'rand';
        }
        // Valid characters for default password (PONDER: make configurable ?)
        $passwdchars = 'abcdefghijklmnopqrstuvwxyz.,-*!:+=()/&%$<>?#@ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = "";
        while ($length--)
        {
            $password .= $passwdchars[$rand(0, strlen($passwdchars) - 1)];
        }
        return $password;
    }

    /**
     * returns an auto generated password which will pass the persons check_password_strength test
     * @param org_openpsa_contacts_person_dba person: the person the password should be generated for
     * @param int length: the number of chars the password will contain
     * @return string password: the generated password
     */
    public static function generate_safe_password($person,$length=0){
        if(!$person){
            return false;
        }
        do{
            $password = self::generate_password($length);
        }while(!$person->check_password_strength($password));
        return $password;
    }

    private function _generate_password()
    {
        $this->_request_data["default_password"] = self::generate_password($this->_config->get('default_password_lenght'));
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_account_edit($handler_id, $args, &$data)
    {
        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);

        // Check if we get the person
        $this->_person = new org_openpsa_contacts_person_dba($args[0]);
        $_MIDCOM->auth->require_do('midgard:update', $this->_person);

        if (   $this->_person->id != midcom_connection::get_user()
            && !midcom_connection::is_admin())
        {
            throw new midcom_error_forbidden('Only admins can edit other user\'s accounts');
        }

        //get existing account for gui
        $this->_account = new midcom_core_account($this->_person);

        if (!$this->_account->get_password())
        {
            // Account needs to be created first, relocate
            $_MIDCOM->relocate($prefix . "account/create/" . $this->_person->guid . "/");
        }

        $data['controller'] = $this->get_controller('nullstorage');
        $formmanager = $data["controller"]->formmanager;

        switch ($data['controller']->process_form())
        {
            case 'save':
                $this->_update_account($formmanager->_types);
                // Account updated, redirect to person card
                $_MIDCOM->relocate($prefix . "person/" . $this->_person->guid . "/");

            case 'cancel':
                $_MIDCOM->relocate($prefix . "person/" . $this->_person->guid . "/");
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
                MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_do('midgard:update', $this->_person),
            )
        );
    }

    private function _update_account($fields)
    {
        $stat = false;

        //check user auth if current user is not admin
        if (!$_MIDCOM->auth->admin)
        {
            //user auth
            $check_user = midcom_connection::login($this->_account->get_username(), $fields["old_password"]->value);
        }
        else
        {
            $check_user = true;
        }

        if (!$check_user)
        {
            $_MIDCOM->uimessages->add($this->_l10n->get('org.openpsa.contacts'), $this->_l10n->get("wrong current password"), 'error');
        }
        //auth ok
        else
        {
            //new password?
            if(!empty($fields["new_password"]->value)){
                $password = $fields["new_password"]->value;
            }else{
                $password = $this->_account->get_password();
            }
            // Update account
            $stat = $this->_person->set_account($fields["username"]->value, $password);
            if (!$stat)
            {
                // Failure, give a message
                $_MIDCOM->uimessages->add($this->_l10n->get('org.openpsa.contacts'), $this->_l10n->get("failed to update user account, reason ") . midcom_connection::get_error_string(), 'error');
            }
        }

        return $stat;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_account_delete($handler_id, $args, &$data)
    {
        // Check if we get the person
        $this->_person = new org_openpsa_contacts_person_dba($args[0]);
        $_MIDCOM->auth->require_do('midgard:update', $this->_person);

        if (   $this->_person->id != midcom_connection::get_user()
            && !midcom_connection::is_admin())
        {
            throw new midcom_error_forbidden('Only admins can delete other user\'s accounts');
        }
        $this->_account = new midcom_core_account($this->_person);
        if (!$this->_account->get_username())
        {
            // Account needs to be created first, relocate
            $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
            $_MIDCOM->relocate($prefix . "person/" . $this->_person->guid . "/");
        }

        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
        if (   array_key_exists('midcom_helper_datamanager2_save', $_POST)
            && $this->_account->delete())
        {
            // Account updated, redirect to person card
            $_MIDCOM->relocate($prefix . "person/" . $this->_person->guid . "/");
        }
        else if (array_key_exists('midcom_helper_datamanager2_cancel', $_POST))
        {
            $_MIDCOM->relocate($prefix . "person/" . $this->_person->guid . "/");
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
        $this->add_breadcrumb("person/{$this->_person->guid}/", $this->_person->name);
        $this->add_breadcrumb("", $this->_l10n->get($action));
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_account_create($handler_id, &$data)
    {
        midcom_show_style("show-person-account-create");
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_account_edit($handler_id, &$data)
    {
        midcom_show_style("show-person-account-edit");
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_account_delete($handler_id, &$data)
    {
        midcom_show_style("show-person-account-delete");
    }
}
?>