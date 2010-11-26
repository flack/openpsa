<?php
/**
 * @package net.nehmer.account
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: maintain.php 25323 2010-03-18 15:54:35Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Account Management handler class: Account Publishing class
 *
 * This class implements the account publishing view, which lets the user choose from
 * all user-viewable tagged fields.
 *
 * Summary of available request keys, depending on the actual handler not all of them
 * might be in use, see the various _handler_xxx methods for details:
 *
 * - datamanager: A reference to the DM2 Instance encapsulating the account.
 * - formmanager: The form manager used to draw the form (only available in the edit mode).
 * - profile_url: Contains the URL to the full profile record.
 * - processing_msg: This is the processing message originating from the last request. May be
 *   an empty string. It is localized.
 *
 * @package net.nehmer.account
 */

class net_nehmer_account_handler_maintain extends midcom_baseclasses_components_handler
{
    /**
     * The user account we are managing. This is taken from the currently active user
     * if no account is specified in the URL, or from the GUID passed to the system.
     *
     * @var midcom_db_person
     * @access private
     */
    var $_account = null;

    /**
     * The datamanager used to load the account-related information.
     *
     * @var midcom_helper_datamanager2_datamanager
     * @access private
     */
    var $_datamanager = null;

    /**
     * The controller used to display the password changer dialog.
     *
     * @var midcom_helper_datamanager2_controller
     * @access private
     */
    var $_controller = null;

    /**
     * Helper variable, containing a localized message to be shown to the user indicating the form's
     * processing state.
     *
     * @var string
     * @access private
     */
    var $_processing_msg = '';

    /**
     * The raw, untranslated processing message. Use this if you want to have your own translation
     * beside the defaults given by the component. The variable contains the l10n string IDs.
     *
     * @var string
     * @access private
     */
    var $_processing_msg_raw = '';

    /**
     * This is true if we did successfully change the password. It will then display a simple
     * password-changed-successfully response.
     *
     * @var boolean
     * @access private
     */
    var $_success = false;

    /**
     * The handler provides publishing support. After creating and preparing all members,
     * it will first process the form. Afterwards, it provides the means to display the
     * publishing form.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_password($handler_id, $args, &$data)
    {
        if (!$this->_config->get('allow_change_password'))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Password changing is disabled');
            // This will exit()
        }
        $_MIDCOM->auth->require_valid_user();
        $this->_account = $_MIDCOM->auth->user->get_storage();
        net_nehmer_account_viewer::verify_person_privileges($this->_account);
        $_MIDCOM->auth->require_do('midgard:update', $this->_account);
        $_MIDCOM->auth->require_do('midgard:parameters', $this->_account);

        $this->_prepare_datamanager();
        $this->_prepare_pwchange_formmanager();
        $this->_process_pwchange_form();
        $this->_prepare_request_data();

        $_MIDCOM->set_26_request_metadata(time(), $this->_topic->guid);

        $this->set_active_leaf(NET_NEHMER_ACCOUNT_LEAFID_PASSWORDCHANGE);
        $this->_view_toolbar->hide_item('password/');

        $_MIDCOM->bind_view_to_object($this->_account, $this->_datamanager->schema->name);

        $_MIDCOM->set_pagetitle($this->_l10n->get('change password'));

        return true;
    }

    /**
     * This function prepares the form manager used to change the password.
     */
    function _prepare_pwchange_formmanager()
    {
        $this->_controller = midcom_helper_datamanager2_controller::create('nullstorage');
        $schemadb = midcom_helper_datamanager2_schema::load_database('file:/net/nehmer/account/config/schemadb_internal.inc');
        // Add further validation rules
        // Password is not subject to validation at this point, this can only be done
        // during processing, as the password might be crypted.
        $schemadb['pwchange']->fields['newpassword']['validation'][] = Array
        (
            'type' => 'minlength',
            'format' => $this->_config->get('password_minlength'),
            'message' => sprintf
            (
                $this->_l10n->get('password minlength %d characters'),
                $this->_config->get('password_minlength')
            ),
        );
        $this->_controller->schemadb = $schemadb;
        $this->_controller->schemaname = 'pwchange';
        $this->_controller->defaults = Array();
        $this->_controller->initialize();
    }

    /**
     * This function processes the form, computing the visible field list for the current
     * selection. If no form submission can be found, the method exits unconditionally.
     */
    function _process_pwchange_form()
    {
        switch ($this->_controller->process_form())
        {
            case 'save':
                if (! $this->_check_old_password())
                {
                    $this->_processing_msg = $this->_l10n->get('old password invalid.');
                    $this->_processing_msg_raw = 'old password invalid.';
                }
                else
                {
                    $this->_update_password();
                    $this->_processing_msg = $this->_l10n->get('password changed.');
                    $this->_processing_msg_raw = 'password changed.';
                    $this->_success = true;
                }
                break;

            case 'cancel':
                $_MIDCOM->relocate('');
                // This will exit.
        }
        // Still editing...
    }

    /**
     * This helper will update the password to the new value and synchronize the last changed
     * timestamp accordingly.
     */
    function _update_password()
    {
        $new_password = $this->_controller->datamanager->types['newpassword']->value;
        if (! $_MIDCOM->auth->user->update_password($new_password, false))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to update the password');
            // This will exit.
        }
    }

    /**
     * This function checks the old password entered into the pwchange_controller
     * against the account password.
     */
    function _check_old_password()
    {
        // We check the entered password against the possibly crypted one:
        $salt = substr($this->_account->password, 0, 2);
        $entered_password = $this->_controller->datamanager->types['oldpassword']->value;
        if ($salt == '**')
        {
            $compare_to = "**{$entered_password}";
        }
        else
        {
            $compare_to = crypt($entered_password, $salt);
        }
        return ($compare_to == $this->_account->password);
    }

    /**
     * This function prepares the requestdata with all computed values.
     * A special case is the visible_data array, which maps field names
     * to prepared values, which can be used in display directly. The
     * information returned is already HTML escaped.
     *
     * @access private
     */
    function _prepare_request_data()
    {
        $this->_request_data['datamanager'] =& $this->_datamanager;
        $this->_request_data['formmanager'] =& $this->_controller->formmanager;
        $this->_request_data['processing_msg'] = $this->_processing_msg;
        $this->_request_data['processing_msg_raw'] = $this->_processing_msg_raw;
        $this->_request_data['profile_url'] = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
    }

    /**
     * Internal helper function, prepares a datamanager based on the current account.
     */
    function _prepare_datamanager()
    {
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_account'));
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($schemadb);
        $this->_datamanager->autoset_storage($this->_account);
    }

    /**
     * Shows the the password changing dialog.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_password($handler_id, &$data)
    {
        if ($this->_success)
        {
            midcom_show_style('show-password-change-ok');
        }
        else
        {
            midcom_show_style('show-password-change');
        }
    }

    /**
     * This function prepares everything to update the username, it basically follows the
     * same procedure as handle_password.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_username($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();
        $this->_account = $_MIDCOM->auth->user->get_storage();
        net_nehmer_account_viewer::verify_person_privileges($this->_account);
        $_MIDCOM->auth->require_do('midgard:update', $this->_account);
        $_MIDCOM->auth->require_do('midgard:parameters', $this->_account);

        $this->_prepare_datamanager();
        $this->_prepare_usernamechange_formmanager();
        $this->_process_usernamechange_form();
        $this->_prepare_request_data();

        $_MIDCOM->set_26_request_metadata(time(), $this->_topic->guid);

        $tmp[] = array
        (
            MIDCOM_NAV_URL => 'username/',
            MIDCOM_NAV_NAME => $this->_l10n->get('change username'),
        );
        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);
        $this->_view_toolbar->hide_item('username/');

        $_MIDCOM->bind_view_to_object($this->_account, $this->_datamanager->schema->name);

        $_MIDCOM->set_pagetitle($this->_l10n->get('change username'));

        return true;
    }

    /**
     * This function prepares the form manager used to change the password.
     */
    function _prepare_usernamechange_formmanager()
    {
        $this->_controller = midcom_helper_datamanager2_controller::create('nullstorage');
        $schemadb = midcom_helper_datamanager2_schema::load_database('file:/net/nehmer/account/config/schemadb_internal.inc');
        $this->_controller->schemadb = $schemadb;
        $this->_controller->schemaname = $this->_config->get('username_is_email') ?
            'emailusernamechange' : 'usernamechange';
        $this->_controller->defaults = Array('username' => $this->_account->username);
        $this->_controller->initialize();

        // Add further validation rules, this is done with the form directly,
        // as we have to register the callback first. We have to load the callback
        // file manually, as we don't add it to the standard loader code.
        // This will prohibit duplicate user names.
        require_once(MIDCOM_ROOT . '/net/nehmer/account/callbacks/validation.php');
        $this->_controller->formmanager->form->registerRule
        (
            'check_user_name',
            'callback',
            'check_user_name',
            'net_nehmer_account_callbacks_validation'
        );
        $this->_controller->formmanager->form->addRule
        (
            'username',
            $this->_l10n->get('the username is already in use.'),
            'check_user_name',
            $this->_account->username
        );
        $this->_controller->formmanager->form->addRule
        (
            'username',
            $this->_l10n->get('invalid username'),
            'regex',
            '/^[^\+\*!]+$/'
        );
        if ($this->_config->get('username_is_email'))
        {
            $this->_controller->formmanager->form->addRule
            (
                'username',
                $this->_l10n->get('invalid username email'),
                'email'
            );
        }
    }

    /**
     * This function processes the form, computing the visible field list for the current
     * selection. If no form submission can be found, the method exits unconditionally.
     */
    function _process_usernamechange_form()
    {
        switch ($this->_controller->process_form())
        {
            case 'save':
                $new = $this->_controller->datamanager->types['username']->value;
                if ($new == $this->_account->username)
                {
                    $this->_processing_msg = $this->_l10n->get('you must enter a new username.');
                    $this->_processing_msg_raw = 'you must enter a new username.';
                }
                else
                {
                    if (   $this->_config->get('username_is_email')
                        && $_MIDCOM->auth->request_sudo())
                    {
                        $person = $this->_account->get_storage();
                        $person->email = $new;
                        $person->update();
                        $_MIDCOM->auth->drop_sudo();
                    }
                    $_MIDCOM->auth->user->update_username($new);
                    $this->_processing_msg = $this->_l10n->get('username changed.');
                    $this->_processing_msg_raw = 'username changed.';
                    $this->_success = true;
                }
                break;

            case 'cancel':
                $_MIDCOM->relocate('');
                // This will exit.
        }
        // Still editing...
    }

    /**
     * Shows either the username change dialog or a succcess message.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_username($handler_id, &$data)
    {
        if ($this->_success)
        {
            midcom_show_style('show-username-change-ok');
        }
        else
        {
            midcom_show_style('show-username-change');
        }
    }

    /**
     * This function prepares everything to update the username, it basically follows the
     * same procedure as handle_password.
     *
     * It uses only a nullstorage formmanager and a processing message in the Request
     * Data storage.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_lostpassword($handler_id, $args, &$data)
    {
        $this->_prepare_lostpassword_formmanager();
        $this->_process_lostpassword_form();
        $this->_prepare_request_data();

        if ($this->_datamanager)
        {
            $_MIDCOM->substyle_append($this->_datamanager->schema->name);
        }
        $_MIDCOM->set_26_request_metadata(time(), $this->_topic->guid);
        $this->set_active_leaf(NET_NEHMER_ACCOUNT_LEAFID_LOSTPASSWORD);
        $_MIDCOM->set_pagetitle($this->_l10n->get('lost password'));

        return true;
    }

    /**
     * Password reset activation hash handler
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_lostpassword_reset($handler_id, $args, &$data)
    {
        $guid = $args[0];
        $hash = $args[1];

        if (! $_MIDCOM->auth->request_sudo('net.nehmer.account'))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to request sudo privileges for account password reset.');
            // This will exit.
        }

        $person = new midcom_db_person($guid);
        if (!$person->guid)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, 'Invalid reset link, the person record was not found.');
            // This will exit.
        }

        $this->_account = $_MIDCOM->auth->get_user($person);

        $reset_hash = $person->get_parameter('net.nehmer.account', 'lostpassword_reset_hash');

        if ($reset_hash != $hash)
        {
            if ($reset_hash)
            {
                // wrong reset hash has been passed.
                $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, 'Invalid reset link.');
                // This will exit
            }
            $this->_processing_msg = $this->_l10n->get('password already reset');
            $this->_processing_msg_raw = 'password already reset';
        }
        else
        {
            $this->_reset_password($person->username);

            //Cleanup
            $person->delete_parameter('net.nehmer.account', 'lostpassword_reset_hash');
            $person->delete_parameter('net.nehmer.account', 'lostpassword_reset_hash_created');
            $person->delete_parameter('net.nehmer.account', 'lostpassword_reset_link');

            $this->_processing_msg = $this->_l10n->get('password reset, mail sent.');
            $this->_processing_msg_raw = 'password reset, mail sent.';
        }

        $_MIDCOM->auth->drop_sudo();

        $this->_prepare_request_data();

        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_lostpassword_reset($handler_id, &$data)
    {
        midcom_show_style('show-lostpassword-ok');
    }

    function _send_lostpassword_reset_link($email, $username=false)
    {
        if (! $_MIDCOM->auth->request_sudo('net.nehmer.account'))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to request sudo privileges for account password reset.');
            // This will exit.
        }

        if ($username)
        {
            $user = $_MIDCOM->auth->get_user_by_name($username);
        }
        else
        {
            $user = $_MIDCOM->auth->get_user_by_email($email);
        }

        if (! $user)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Failed to find user with given email {$email}.");
            debug_pop();
            $this->_processing_msg = $this->_l10n->get('failed to find user with given email');
            $this->_processing_msg_raw = 'failed to find user with given email';
            return false;
        }
        if (is_array($user))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Found multiple users with given email {$email}.");
            debug_pop();
            $this->_processing_msg = $this->_l10n->get('multiple users found with given email');
            $this->_processing_msg_raw = 'multiple users found with given email';
            return false;
        }

        $person =& $user->get_storage();

        // Generate activation hash by entering unique enough information
        $reset_hash = md5
        (
              serialize(microtime())
            . $person->username
            . serialize($_MIDGARD)
            . serialize($_SERVER)
        );

        // Generate the activation link
        $reset_link = $_MIDCOM->get_host_name() . $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . "lostpassword/reset/{$person->guid}/{$reset_hash}/";

        // Store the information in parameters for activation
        $person->set_parameter('net.nehmer.account', 'lostpassword_reset_hash', $reset_hash);
        $person->set_parameter('net.nehmer.account', 'lostpassword_reset_hash_created', strftime('%Y-%m-%d', time()));

        // Store the reset link so that it can be fetched straight from the person record
        $person->set_parameter('net.nehmer.account', 'lostpassword_reset_link', $reset_link);

        $_MIDCOM->auth->drop_sudo();

        net_nehmer_account_viewer::send_password_reset_mail($person, $reset_link, $this->_config);

        return true;
    }


    /**
     * This function prepares the form manager used to change the password.
     */
    function _prepare_lostpassword_formmanager()
    {
        $include_username = false;

        if (   isset($_POST['email'])
            && $this->_config->get('lostpassword_email_reset'))
        {
            $user = $_MIDCOM->auth->get_user_by_email($_POST['email']);
            if (is_array($user))
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Found multiple users with given email {$_POST['email']}.");
                debug_pop();
                $this->_processing_msg = $this->_l10n->get('multiple users found with given email');
                $this->_processing_msg_raw = 'multiple users found with given email';

                $include_username = true;
            }
        }

        $this->_controller = midcom_helper_datamanager2_controller::create('nullstorage');
        $schemadb = midcom_helper_datamanager2_schema::load_database('file:/net/nehmer/account/config/schemadb_internal.inc');
        $this->_controller->schemadb = $schemadb;
        $this->_controller->schemaname = 'lostpassword';
        if ($this->_config->get('lostpassword_email_reset'))
        {
            $this->_controller->schemaname = 'lostpassword_by_email';
            if ($include_username)
            {
                $this->_controller->schemaname = 'lostpassword_by_email_username';
            }
        }
        $this->_controller->initialize();

        // Add further validation rules, this is done with the form directly,
        // as we have to register the callback first. We have to load the callback
        // file manually, as we don't add it to the standard loader code.
        // This will prohibit duplicate user names.
        require_once(MIDCOM_ROOT . '/net/nehmer/account/callbacks/validation.php');

        if($this->_config->get('lostpassword_email_reset'))
        {
            $this->_controller->formmanager->form->registerRule
            (
                'verify_existing_user_email',
                'callback',
                'verify_existing_user_email',
                'net_nehmer_account_callbacks_validation'
            );

            if ($include_username)
            {
                $this->_controller->formmanager->form->registerRule
                (
                    'verify_existing_user_name',
                    'callback',
                    'verify_existing_user_name',
                    'net_nehmer_account_callbacks_validation'
                );
            }

            if ($this->_account)
            {
                $this->_controller->formmanager->form->addRule
                (
                    'email',
                     $this->_l10n->get('the email is unknown'),
                    'verify_existing_user_email',
                    $this->_account->email
                );
            }
        }
        else
        {
            $this->_controller->formmanager->form->registerRule
            (
                'verify_existing_user_name',
                'callback',
                'verify_existing_user_name',
                'net_nehmer_account_callbacks_validation'
            );

            if ($this->_account)
            {
                $this->_controller->formmanager->form->addRule
                (
                    'username',
                     $this->_l10n->get('the username is unknown.'),
                    'verify_existing_user_name',
                    $this->_account->username
                );
            }
        }
    }

    /**
     * This function processes the form, creating a new password and mailing it to the
     * user in question.
     */
    function _process_lostpassword_form()
    {
        switch ($this->_controller->process_form())
        {
            case 'save':
                if ($this->_config->get('lostpassword_email_reset'))
                {
                    $email = $this->_controller->datamanager->types['email']->value;
                    $username = false;
                    $send_email = true;
                    if (isset($this->_controller->datamanager->types['username']))
                    {
                        $username = $this->_controller->datamanager->types['username']->value;
                        $user = $_MIDCOM->auth->get_user_by_name($username);
                        if ($user)
                        {
                            $person =& $user->get_storage();
                            if ($person->email != $email)
                            {
                                $this->_processing_msg = $this->_l10n->get("username and email doesn't match");
                                $this->_processing_msg_raw = "username and email doesn't match";
                                $send_email = false;
                            }
                        }
                    }

                    if ($send_email)
                    {
                        if ($this->_send_lostpassword_reset_link($email, $username))
                        {
                            $this->_processing_msg = $this->_l10n->get('password reset request sent, check your email.');
                            $this->_processing_msg_raw = 'password reset request sent, check your email.';
                            $this->_success = true;
                        }
                    }
                }
                else
                {
                    $this->_reset_password($this->_controller->datamanager->types['username']->value);
                    $this->_processing_msg = $this->_l10n->get('password reset, mail sent.');
                    $this->_processing_msg_raw = 'password reset, mail sent.';
                    $this->_success = true;
                }

                break;

            case 'cancel':
                $_MIDCOM->relocate('');
                // This will exit.
        }
        // Still editing...
    }

    /**
     * This is an internal helper function, resetting the password for the given
     * username to a randomly generated one.
     *
     * We assume that the username has been validated by the QuickForm already,
     * in case we cannot retrieve the record, we just trigger a generate_error.
     *
     * @param string $username The name of the user who wants his password reset.
     */
    function _reset_password($username)
    {
        if (! $_MIDCOM->auth->request_sudo('net.nehmer.account'))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                'Failed to request sudo privileges for account creation.');
            // This will exit.
        }

        $user = $_MIDCOM->auth->get_user_by_name($username);
        if (! $user)
        {
            $_MIDCOM->auth->drop_sudo();
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                "The username {$username} is unknown. For some reason the QuickForm validation failed.");
            // This will exit.
        }

        // Generate a random password
        $length = max(8, $this->_config->get('password_minlength'));
        $password = midcom_admin_user_plugin::generate_password($length);

        if (! $user->update_password($password, false))
        {
            $_MIDCOM->auth->drop_sudo();
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                "Could not update the password of username {$username}: " . midcom_connection::get_error_string());
            // This will exit.
        }

        $person = $user->get_storage();

        $_MIDCOM->auth->drop_sudo();

        $this->_send_reset_mail($person, $password);
    }

    /**
     * This is a simple function which generates and sends a password reset mail.
     *
     * @param midcom_db_person $person The newly created person account.
     */
    function _send_reset_mail($person, $password)
    {
        $from = $this->_config->get('password_reset_mail_sender');
        if (! $from)
        {
            $from = $person->email;
        }
        $template = array
        (
            'from' => $from,
            'reply-to' => '',
            'cc' => '',
            'bcc' => '',
            'x-mailer' => '',
            'subject' => $this->_l10n->get($this->_config->get('password_reset_mail_subject')),
            'body' => $this->_l10n->get($this->_config->get('password_reset_mail_body')),
            'body_mime_type' => 'text/plain',
            'charset' => 'UTF-8',
        );

        $mail = new midcom_helper_mailtemplate($template);
        $parameters = array
        (
            'PERSON' => $person,
            'PASSWORD' => $password,
        );
        $mail->set_parameters($parameters);
        $mail->parse();
        $mail->send($person->email);
    }

    /**
     * Shows either the username change dialog or a success message.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_lostpassword($handler_id, &$data)
    {
        if ($this->_success)
        {
            midcom_show_style('show-lostpassword-ok');
        }
        else
        {
            midcom_show_style('show-lostpassword');
        }
    }

    /**
     * This function prepares everything to cancel the membership, it basically follows the
     * same procedure as handle_password.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_cancel_membership($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();
        $this->_account = $_MIDCOM->auth->user->get_storage();
        net_nehmer_account_viewer::verify_person_privileges($this->_account);
        $_MIDCOM->auth->require_do('midgard:update', $this->_account);
        $_MIDCOM->auth->require_do('midgard:parameters', $this->_account);

        $this->_prepare_datamanager();
        $this->_process_cancel_membership();
        // This could relocate

        if (! $this->_success)
        {
            $this->_request_data['confirmation_hash'] = $this->_compute_cancel_membership_confirm_hash();
        }

        $this->_prepare_request_data();

        $_MIDCOM->set_26_request_metadata(time(), $this->_topic->guid);

        $tmp[] = Array
        (
            MIDCOM_NAV_URL => 'cancel_membership/',
            MIDCOM_NAV_NAME => $this->_l10n->get('cancel membership'),
        );
        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);
        $this->_view_toolbar->hide_item('cancel_membership/');

        $_MIDCOM->bind_view_to_object($this->_account, $this->_datamanager->schema->name);

        $_MIDCOM->set_pagetitle($this->_l10n->get('cancel membership'));

        return true;
    }

    /**
     * Actual membership cancellation, looks for the REQUEST key cancel_ok and then
     * invalidates the password and sets a property about when the account was disabled.
     * It will drop the current login session explicitly. Although reauthentication in
     * the next request wouldn't be possible anyway, we do this for safety reasons
     * nevertheless.
     */
    function _process_cancel_membership()
    {
        if (array_key_exists('net_nehmer_account_deleteok', $_REQUEST))
        {
            $confirmation_hash = $this->_compute_cancel_membership_confirm_hash();
            if (   ! array_key_exists('confirmation_hash', $_REQUEST)
                || $_REQUEST['confirmation_hash'] != $confirmation_hash)
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                    'Invalid confirmation hash specified, mebership will not be cancelled.');
                // This will exit.
            }

            // If a callback is set, invoke it now.
            $this->_invoke_cancel_membership_callback();
            $this->_drop_account();
            $_MIDCOM->auth->drop_login_session();
            $this->_success = true;
        }
        else if (array_key_exists('net_nehmer_account_deletecancel', $_REQUEST))
        {
            $_MIDCOM->relocate('');
            // This will exit.
        }
    }

    /**
     * Helper function which takes a number of login session specific items into account,
     * that should protect against social-hacking attacks on a user not actually wanting
     * to cancel his membership.
     *
     * Based on:
     *
     * - Current login session GUID.
     * - Client IP.
     * - Some stat() information of this file.
     *
     * This should be reasonably unique to prevent social hacking.
     *
     * @return string The generated hash.
     */
    function _compute_cancel_membership_confirm_hash()
    {
        $hash_source = $_MIDCOM->auth->sessionmgr->current_session_id
            . $_SERVER["REMOTE_ADDR"]
            . filectime(__FILE__)
            . __FILE__;
        $hash = md5($hash_source);
        debug_add("Computed this confirmation hash {$hash} using this as a basis: {$hash_source}");
        return $hash;
    }


    /**
     * This function invokes the callback set in the component configuration upon
     * deleting an account. It will be executed immediately before the actual delete
     * operation.
     *
     * Configuration syntax:
     * <pre>
     * 'on_cancel_membership' => Array
     * (
     *     'callback' => 'callback_function_name',
     *     'autoload_snippet' => 'snippet_name', // optional
     *     'autoload_file' => 'filename', // optional
     * ),
     * </pre>
     *
     * The callback function will receive the midcom_db_person object instance as an argument.
     *
     * @access private
     */
    function _invoke_cancel_membership_callback()
    {
        $callback = $this->_config->get('on_cancel_membership');
        if ($callback)
        {
            // Try autoload:
            if (array_key_exists('autoload_snippet', $callback))
            {
                mgd_include_snippet_php($callback['autoload_snippet']);
            }
            if (array_key_exists('autoload_file', $callback))
            {
                require_once($callback['autoload_file']);
            }

            if (! function_exists($callback['callback']))
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Failed to load the callback {$callback['callback']} for account deletion, the function is not defined.", MIDCOM_ERRCRIT);
                debug_pop();
                return;
            }
            $callback['callback']($this->_account);
        }
    }

    /**
     * Actually deletes the current account. On any error, generate_error is triggered.
     */
    function _drop_account()
    {
        $user = $_MIDCOM->auth->get_user($this->_account);
        if (! $user->delete())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                'Failed to delete the user account, last Midgard error was: ' . midcom_connection::get_error_string());
            // This will exit.
        }
    }

    /**
     * Shows either the mebership cancel confirmation dialog or a succcess message.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_cancel_membership($handler_id, &$data)
    {
        if ($this->_success)
        {
            midcom_show_style('show-cancel-membership-ok');
        }
        else
        {
            midcom_show_style('show-cancel-membership');
        }
    }

}

?>