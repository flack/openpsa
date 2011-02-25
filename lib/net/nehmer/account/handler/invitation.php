<?php
/**
 * @package net.nehmer.account
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Account Management handler class: Invitation
 *
 * This class allows you to invite people.
 *
 * @package net.nehmer.account
 */
class net_nehmer_account_handler_invitation extends midcom_baseclasses_components_handler
{
    private $_mail = null;
    private $_invite = null;
    private $_sent_invites = null;
    private $_processing_msg_raw = "";
    private $_user_defined_message = "";
    private $_contactgrabber = null;

    /**
     * _on_initialize is called by midcom on creation of the handler.
     */
    public function _on_initialize()
    {
        $_MIDCOM->load_library('org.openpsa.mail');

        if ($_MIDCOM->componentloader->is_installed('com.magnettechnologies.contactgrabber'))
        {
            $_MIDCOM->load_library('com.magnettechnologies.contactgrabber');
            $this->_contactgrabber = new com_magnettechnologies_contactgrabber();
        }
        $this->_request_data['contactgrabber'] =& $this->_contactgrabber;
    }


    /**
     * Checks if user is already registered
     */
    private function _is_person_registered($email)
    {
        $qb = midcom_db_person::new_query_builder();
        $qb->add_constraint('email', '=', $email);

        $persons = $qb->execute();

        if (count($persons) > 0)
        {
            return $persons;
        }
        else
        {
            return false;
        }
    }

    /**
     * Adds a buddy
     */
    private function _add_as_buddy($buddy_user_guid)
    {
        if (!$_MIDCOM->componentloader->is_loaded('net.nehmer.buddylist'))
        {
            if ($_MIDCOM->componentloader->load_graceful('net.nehmer.buddylist'))
            {
                $_MIDCOM->auth->require_valid_user();

                // Setup.
                $buddy_user = $_MIDCOM->auth->get_user($buddy_user_guid);
                if (!$buddy_user)
                {
                    debug_add("The user guid {$buddy_user} is unknown.");
                }

                if (net_nehmer_buddylist_entry::is_on_buddy_list($buddy_user))
                {
                    $this->_processing_msg_raw = 'user already on your buddylist.';
                }
                else
                {
                    $entry = new net_nehmer_buddylist_entry();
                    $entry->account = $_MIDCOM->auth->user->guid;
                    $entry->buddy = $buddy_user->guid;
                    $entry->create();
                    $this->_processing_msg_raw = 'buddy request sent.';
                }
            }
        }
    }

    /**
     * Sending invitations
     */
    private function _send_email_invitation($email, $name='')
    {
        if (!$_MIDCOM->auth->user->_storage->email)
        {
            $_MIDCOM->auth->user->_storage->email = "webmaster@{$_SERVER['HTTP_HOST']}";
        }

        $_MIDCOM->load_library('org.openpsa.mail');

        debug_add("Sending email to {$email}, {$name}");
        $this->_mail = new org_openpsa_mail();
        $this->_mail->to = $email;
        $this->_mail->from = $this->_config->get('invitation_mail_sender');
        $this->_mail->subject = sprintf($this->_l10n->get($this->_config->get('invitation_mail_subject')), $_MIDCOM->auth->user->name);
        // This may be a hack, but it allows us tons more control in rendering the email
        $_MIDCOM->style->enter_context(0);
        $this->_request_data['sender'] =& $_MIDCOM->auth->user->get_storage();
        $this->_request_data['user_message'] = $this->_user_defined_message;
        ob_start();
        midcom_show_style('invitation-email-body');
        $this->_mail->body = ob_get_contents();
        ob_end_clean();
        $_MIDCOM->style->leave_context();

        if (!$this->_mail->send())
        {
            debug_add("Sending invitation email failed: " . $this->_mail->_backend->error->getMessage(), MIDCOM_LOG_ERROR);
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_remind_invite($handler_id, $args, &$data)
    {
        if (!$this->_config->get('allow_invite'))
        {
            throw new midcom_error('Invite functionality is disabled');
        }

        $this->_request_data['hash'] = $args[0];

        $qb = net_nehmer_account_invites_invite_dba::new_query_builder();
        $qb->add_constraint('hash', '=', $args[0]);

        $invites = $qb->execute();
        foreach ($invites as $invite)
        {
            $this->_send_email_invitation($invite->email);
        }

        $_MIDCOM->relocate('sent_invites');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_delete_invite($handler_id, $args, &$data)
    {
        if (!$this->_config->get('allow_invite'))
        {
            throw new midcom_error('Invite functions are disabled');
        }

        $qb = net_nehmer_account_invites_invite_dba::new_query_builder();
        $qb->add_constraint('hash', '=', $args[0]);

        $invites = $qb->execute();

        foreach($invites as $invite)
        {
            $invite->delete();
        }

        $_MIDCOM->relocate('sent_invites');
    }

    /**
     * This method is never called, as the handler method will always relocate
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_delete_invite($handler_id, &$data)
    {
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_invite($handler_id, $args, &$data)
    {
        if (!$this->_config->get('allow_invite'))
        {
            throw new midcom_error('Invite functions are disabled');
        }

        $_MIDCOM->auth->require_valid_user();

        if (isset($_POST['net_nehmer_accounts_invitation_submit']))
        {
            for ($i = 0; $i < $_POST['net_nehmer_accounts_invitation_total_contacts']; $i++)
            {
                if ($i >= $this->_config->get('email_fields') && !isset($_POST["net_nehmer_accounts_invitation_invitee_selected_{$i}"]))
                {
                    continue;
                }

                if (   isset($_POST["net_nehmer_accounts_invitation_invitee_name_{$i}"])
                    && isset($_POST["net_nehmer_accounts_invitation_invitee_email_{$i}"])
                    && !empty($_POST["net_nehmer_accounts_invitation_invitee_name_{$i}"])
                    && !empty($_POST["net_nehmer_accounts_invitation_invitee_email_{$i}"])
                    )
                {
                    if (   isset($_POST['net_nehmer_accounts_invitation_email_message'])
                        && !empty($_POST['net_nehmer_accounts_invitation_email_message']))
                    {
                        $this->_user_defined_message = $_POST['net_nehmer_accounts_invitation_email_message'];
                    }

                   /**
                    * Saving the invite object
                    */
                    $this->_invite = new net_nehmer_account_invites_invite_dba();
                    $this->_invite->hash = md5($_POST["net_nehmer_accounts_invitation_invitee_email_{$i}"] . "_{$_MIDCOM->auth->user->guid}");
                    $this->_invite->email = $_POST["net_nehmer_accounts_invitation_invitee_email_{$i}"];
                    $this->_invite->buddy = $_MIDCOM->auth->user->guid;

                    debug_print_r("Creating invite: ", $this->_invite);

                    $already_registered = $this->_is_person_registered($_POST["net_nehmer_accounts_invitation_invitee_email_{$i}"]);

                    debug_print_r("persons with email " . $_POST["net_nehmer_accounts_invitation_invitee_email_{$i}"] . ":", $already_registered);

                    if ($already_registered)
                    {
                        foreach ($already_registered as $person)
                        {
                            $this->_add_as_buddy($person->guid);
                        }

                        continue;
                    }
                    else
                    {
                        if (!$this->_invite->create())
                        {
                            debug_add("Could not create invite object ID " . $this->_invite->id);
                        }
                    }

                    $this->_request_data['hash'] = $this->_invite->hash;

                    $this->_send_email_invitation
                    (
                        $_POST["net_nehmer_accounts_invitation_invitee_email_{$i}"],
                        $_POST["net_nehmer_accounts_invitation_invitee_name_{$i}"]
                    );
                }
            }
            $_MIDCOM->relocate('sent_invites');
        }

        $step_overrides = $this->_config->get('override_registration_steps');
        if (array_key_exists('invite', $step_overrides))
        {
            $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
            $this->_request_data['skip_url'] = "{$prefix}{$step_overrides['invite']}";
        }

        $_MIDCOM->set_pagetitle($this->_l10n->get('import contacts'));
        $this->add_breadcrumb('invite/', $this->_l10n->get('import contacts'));
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_invite($handler_id, &$data)
    {
        midcom_show_style('show-invite-emails');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_sent_invites($handler_id, $args, &$data)
    {
        if (!$this->_config->get('allow_invite'))
        {
            throw new midcom_error('Invite functions are disabled');
        }

        $_MIDCOM->auth->require_valid_user();

        $qb = net_nehmer_account_invites_invite_dba::new_query_builder();
        $qb->add_constraint('metadata.creator', '=', $_MIDCOM->auth->user->guid);

        $invites = $qb->execute();

        $current_time = time();
        $keep_sent_invites = $this->_config->get('keep_sent_invites');

        /**
         * Removing expired invites
         */
        foreach($invites as $invite)
        {
            if ($current_time > ($invite->metadata->created + $keep_sent_invites * 86400))
            {
                $invite->delete();
            }
        }

        $this->_sent_invites = $qb->execute();

        $_MIDCOM->set_pagetitle($this->_l10n->get('invited contacts'));
        $this->add_breadcrumb('invite/', $this->_l10n->get('import contacts'));
        $this->add_breadcrumb('sent_invites/', $this->_l10n->get('invited contacts'));
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_sent_invites($handler_id, &$data)
    {
        midcom_show_style('invites-list-header');

        foreach ($this->_sent_invites as $invite)
        {
            $this->_request_data['invite'] = $invite;
            midcom_show_style('invites-list-item');
        }

        midcom_show_style('invites-list-footer');
    }
}
?>