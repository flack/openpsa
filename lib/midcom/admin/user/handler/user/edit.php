<?php
/**
 * @package midcom.admin.user
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Style editor class for listing style elements
 *
 * @package midcom.admin.user
 */
class midcom_admin_user_handler_user_edit extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_nullstorage
{
    private $_person;

    private $_account;

    private $_schemadb_name = 'schemadb_person';

    public function _on_initialize()
    {
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.admin.user/usermgmt.css');

        midgard_admin_asgard_plugin::prepare_plugin($this->_l10n->get('midcom.admin.user'), $this->_request_data);
    }

    private function _prepare_toolbar(&$data, $handler_id)
    {
        if (   $this->_config->get('allow_manage_accounts')
            && $this->_person)
        {
            $data['asgard_toolbar']->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "__mfa/asgard/preferences/{$this->_person->guid}/",
                    MIDCOM_TOOLBAR_LABEL => midcom::get('i18n')->get_string('user preferences', 'midgard.admin.asgard'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/configuration.png',
                )
            );
            $data['asgard_toolbar']->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "__mfa/asgard_midcom.admin.user/account/{$this->_person->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('edit account'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/repair.png',
                )
            );

        }

        midgard_admin_asgard_plugin::bind_to_object($this->_person, $handler_id, $data);
    }

    /**
     * Loads and prepares the schema database.
     */
    public function load_schemadb()
    {
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get($this->_schemadb_name));
        if ($this->_schemadb_name == 'schemadb_account')
        {
            if (   isset($_POST['username'])
                && trim($_POST['username']) == '')
            {
                // Remove the password requirement if there is no username present
                foreach ($schemadb as $key => $schema)
                {
                    if (isset($schema->fields['password']))
                    {
                        $schemadb[$key]->fields['password']['widget_config']['require_password'] = false;
                    }
                }
            }
        }
        return $schemadb;
    }

    public function get_schema_defaults()
    {
        return array
        (
            'username' => $this->_account->get_username(),
            'person' => $this->_person->guid
        );
    }

    /**
     * Handler method for listing style elements for the currently used component topic
     *
     * @param string $handler_id Name of the used handler
     * @param mixed $args Array containing the variable arguments passed to the handler
     * @param mixed &$data Data passed to the show method
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->_person = new midcom_db_person($args[0]);
        $this->_person->require_do('midgard:update');

        $data['controller'] = $this->get_controller('simple', $this->_person);

        switch ($data['controller']->process_form())
        {
            case 'save':
                // Show confirmation for the user
                midcom::get('uimessages')->add($this->_l10n->get('midcom.admin.user'), sprintf($this->_l10n->get('person %s saved'), $this->_person->name));
                return new midcom_response_relocate("__mfa/asgard_midcom.admin.user/edit/{$this->_person->guid}/");

            case 'cancel':
                return new midcom_response_relocate('__mfa/asgard_midcom.admin.user/');
        }

        $data['view_title'] = sprintf($this->_l10n->get('edit %s'), $this->_person->name);
        midcom::get('head')->set_pagetitle($data['view_title']);
        $this->_prepare_toolbar($data, $handler_id);
        $this->add_breadcrumb("__mfa/asgard_midcom.admin.user/", $this->_l10n->get($this->_component));
        $this->add_breadcrumb("", $data['view_title']);
    }

    /**
     * Show list of the style elements for the currently edited topic component
     *
     * @param string $handler_id Name of the used handler
     * @param mixed &$data Data passed to the show method
     */
    public function _show_edit($handler_id, array &$data)
    {
        midgard_admin_asgard_plugin::asgard_header();

        $data['handler_id'] = $handler_id;
        $data['l10n'] =& $this->_l10n;
        $data['person'] =& $this->_person;
        midcom_show_style('midcom-admin-user-person-edit');

        midgard_admin_asgard_plugin::asgard_footer();
    }

    /**
     * Handler method for listing style elements for the currently used component topic
     *
     * @param string $handler_id Name of the used handler
     * @param mixed $args Array containing the variable arguments passed to the handler
     * @param mixed &$data Data passed to the show method
     */
    public function _handler_edit_account($handler_id, array $args, array &$data)
    {
        if (!$this->_config->get('allow_manage_accounts'))
        {
            throw new midcom_error('Account management is disabled');
        }

        $this->_person = new midcom_db_person($args[0]);
        $this->_person->require_do('midgard:update');
        $this->_account = new midcom_core_account($this->_person);
        $this->_schemadb_name = 'schemadb_account';

        $data['controller'] = $this->get_controller('nullstorage');

        switch ($data['controller']->process_form())
        {
            case 'save':
                $this->_save_account($data['controller']);
                // Show confirmation for the user
                midcom::get('uimessages')->add($this->_l10n->get('midcom.admin.user'), sprintf($this->_l10n->get('person %s saved'), $this->_person->name));
                return new midcom_response_relocate("__mfa/asgard_midcom.admin.user/edit/{$this->_person->guid}/");

            case 'cancel':
                return new midcom_response_relocate('__mfa/asgard_midcom.admin.user/');
        }

        $data['view_title'] = sprintf(midcom::get('i18n')->get_string('edit %s', 'midcom.admin.user'), $this->_person->name);
        midcom::get('head')->set_pagetitle($data['view_title']);
        midgard_admin_asgard_plugin::bind_to_object($this->_person, $handler_id, $data);

        $this->add_breadcrumb("__mfa/asgard_midcom.admin.user/", $this->_l10n->get($this->_component));
        $this->add_breadcrumb("__mfa/asgard_midcom.admin.user/edit/" . $this->_person->guid . '/', $this->_person->name);
        $this->add_breadcrumb("", $data['view_title']);

        // Add jQuery Form handling for generating passwords with AJAX
        midcom::get('head')->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/jquery.form.js');
    }

    public function _save_account(midcom_helper_datamanager2_controller $controller)
    {
        $password = $controller->formmanager->_types['password']->value;
        $username = $controller->formmanager->_types['username']->value;

        if (   trim($username) == ''
            || trim($password) == '')
        {
            $this->_account->delete();
        }
        else
        {
            $this->_account->set_username($username);
            $this->_account->set_password($password);
            $this->_account->save();
        }
    }

    /**
     * Show list of the style elements for the currently edited topic component
     *
     * @param string $handler_id Name of the used handler
     * @param mixed &$data Data passed to the show method
     */
    public function _show_edit_account($handler_id, array &$data)
    {
        midgard_admin_asgard_plugin::asgard_header();

        $data['handler_id'] = $handler_id;
        $data['l10n'] =& $this->_l10n;
        $data['person'] =& $this->_person;
        midcom_show_style('midcom-admin-user-person-edit-account');

        if (isset($_GET['f_submit']))
        {
            midcom_show_style('midcom-admin-user-generate-passwords');
        }

        midgard_admin_asgard_plugin::asgard_footer();
    }

    /**
     * Auto-generate passwords on the fly
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_passwords($handler_id, array $args, array &$data)
    {
        midcom::get()->skip_page_style = true;
    }

    /**
     * Auto-generate passwords on the fly
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_passwords($handler_id, array &$data)
    {
        // Show passwords
        $data['l10n'] =& $this->_l10n;
        midcom_show_style('midcom-admin-user-generate-passwords');
    }

    /**
     * Internal helper for processing the batch change of passwords
     */
    private function _process_batch_change()
    {
        // Set the mail commo parts
        $mail = new org_openpsa_mail();
        $mail->from = $this->_config->get('message_sender');
        $mail->encoding = 'UTF-8';

        // Success switch
        $success = true;

        // Get the context prefix
        $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);

        // Change every user or continue to next on failure - failures will show UI messages
        foreach ($_POST['midcom_admin_user'] as $id)
        {
            try
            {
                $person = new midcom_db_person($id);
                $account = midcom_core_account::get($person);
            }
            catch (midcom_error $e)
            {
                midcom::get('uimessages')->add($this->_l10n->get('midcom.admin.user'), sprintf($this->_l10n->get('failed to get the user with id %s'), $id), 'error');
                $success = false;
                continue;
            }

            // This shortcut is used in case of errors
            $person_edit_url = "<a href=\"{$prefix}__mfa/asgard_midcom.admin.user/edit/{$person->guid}\">{$person->name}</a>";

            // Cannot send the email if address is not specified
            if (!$person->email)
            {
                midcom::get('uimessages')->add($this->_l10n->get('midcom.admin.user'), sprintf($this->_l10n->get('no email address defined for %s'), $person_edit_url), 'error');
                continue;
            }

            // if account has no username, we don't need to set a password either
            if ($account->get_username() == '')
            {
                midcom::get('uimessages')->add($this->_l10n->get('midcom.admin.user'), sprintf($this->_l10n->get('no username defined for %s'), $person_edit_url), 'error');
                continue;
            }

            // Recipient
            $mail->to = $person->email;

            // Store the old password
            $person->set_parameter('midcom.admin.user', 'old_password', $account->get_password());

            // Get a new password
            $password = midcom_admin_user_plugin::generate_password(8);

            $mail->body = $_POST['body'];
            $mail->subject = $_POST['subject'];

            $mail->parameters = array
            (
                'PASSWORD' => $password,
                'FROM' => $this->_config->get('message_sender'),
                'LONGDATE' => strftime('%c'),
                'SHORTDATE' => strftime('%x'),
                'TIME' => strftime('%X'),
                'FIRSTNAME' => $person->firstname,
                'LASTNAME' => $person->lastname,
                'USERNAME' => $person->username,
                'EMAIL' => $person->email,
            );

            // Send the message
            if ($mail->send())
            {
                // Set the password
                $account->set_password($password);

                if (!$account->save())
                {
                    midcom::get('uimessages')->add($this->_l10n->get('midcom.admin.user'), sprintf($this->_l10n->get('failed to update the password for %s'), $person_edit_url));
                    $success = false;
                }
            }
            else
            {
                throw new midcom_error("Failed to send the mail, SMTP returned error " . $mail->get_error_message());
            }
        }

        // Show UI message on success
        if ($success)
        {
            midcom::get('uimessages')->add($this->_l10n->get('midcom.admin.user'), $this->_l10n->get('passwords updated and mail sent'));
        }
    }

    /**
     * Batch process password change
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_batch($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_admin_user();

        // Set page title and default variables
        $data['view_title'] = $this->_l10n->get('batch generate passwords');
        $data['variables'] = array
        (
            '__FIRSTNAME__' => $this->_l10n->get('firstname'),
            '__LASTNAME__' => $this->_l10n->get('lastname'),
            '__USERNAME__' => $this->_l10n->get('username'),
            '__EMAIL__' => $this->_l10n->get('email'),
            '__PASSWORD__' => $this->_l10n->get('password'),
            '__FROM__' => $this->_l10n->get('sender'),
            '__LONGDATE__' => sprintf($this->_l10n->get('long dateformat (%s)'), strftime('%c')),
            '__SHORTDATE__' => sprintf($this->_l10n->get('short dateformat (%s)'), strftime('%x')),
            '__TIME__' => sprintf($this->_l10n->get('current time (%s)'), strftime('%X')),
        );

        if (   isset($_POST['midcom_admin_user'])
            && count($_POST['midcom_admin_user']) > 0)
        {
            if (isset($_POST['f_cancel']))
            {
                midcom::get('uimessages')->add($this->_l10n->get('midcom.admin.user'), midcom::get('i18n')->get_string('cancelled', 'midcom'));
                return new midcom_response_relocate('__mfa/asgard_midcom.admin.user/');
            }
            $this->_process_batch_change();
            // Relocate to the user administration front page
            return new midcom_response_relocate('__mfa/asgard_midcom.admin.user/');
        }

        if (isset($_GET['ajax']))
        {
            return;
        }

        // Prepare the toolbar and breadcrumb
        $this->_prepare_toolbar($data, $handler_id);

        // Populate breadcrumb
        $this->add_breadcrumb("__mfa/asgard_midcom.admin.user/", $this->_l10n->get('midcom.admin.user'));
        $this->add_breadcrumb('__mfa/asgard_midcom.admin.user/password/batch/', $data['view_title']);
    }

    /**
     * Show the batch password change form
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_batch($handler_id, array &$data)
    {
        if (!isset($_GET['ajax']))
        {
            midgard_admin_asgard_plugin::asgard_header();
            midcom_show_style('midcom-admin-user-password-nonajax-header');
        }

        $data['message_subject'] = $this->_l10n->get($this->_config->get('message_subject'));
        $data['message_body'] = $this->_l10n->get($this->_config->get('message_body'));

        midcom_show_style('midcom-admin-user-password-email');

        if (!isset($_GET['ajax']))
        {
            midcom_show_style('midcom-admin-user-passwords-list');
            midgard_admin_asgard_plugin::asgard_footer();
        }
    }
}
?>