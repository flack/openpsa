<?php
/**
 * @package net.nehmer.account
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: view.php 11271 2007-07-18 12:12:43Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package net.nehmer.account
 */

class net_nehmer_account_handler_pending extends midcom_baseclasses_components_handler
{
    /**
     * Set the breadcrumb path and active leaf
     *
     * @access private
     */
    function _on_initialize()
    {
        // Active leaf of the topic
        $this->set_active_leaf(NET_NEHMER_ACCOUNT_LEAFID_PENDING);

        // Add table sorder
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/jquery.tablesorter.pack.js');
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/net.nehmer.account/jquery.tablesorter.widget.column_highlight.js');
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/net.nehmer.account/twisty.js');
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/net.nehmer.account/net_nehmer_account.css');
    }

    /**
     * List of persons waiting for approval
     *
     * @var Array $persons    Array of midcom_db_person objects
     */
    var $persons = array();

    /**
     * List accounts that are pending for an approval
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success
     */
    public function _handler_list($handler_id, $args, &$data)
    {
        // Require administrator privileges
        $_MIDCOM->auth->require_admin_user();

        $qb = midcom_db_person::new_query_builder();

        $qb->begin_group('AND');
            $qb->add_constraint('parameter.domain', '=', 'net.nehmer.account');
            $qb->add_constraint('parameter.name', '=', 'require_approval');
            $qb->add_constraint('parameter.value', '=', 'require_approval');
        $qb->end_group();

        $qb->add_order('lastname');
        $qb->add_order('firstname');

        $this->persons = $qb->execute_unchecked();

        return true;
    }

    /**
     * Show list of pending approvals
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_list($handler_id, &$data)
    {
        if (count($this->persons) === 0)
        {
            midcom_show_style('pending-accounts-none');
            return;
        }

        midcom_show_style('pending-accounts-list-header');

        // Show the list of persons pending for approval
        foreach ($this->persons as $person)
        {
            $data['person'] =& $person;
            midcom_show_style('pending-accounts-list-person');
        }

        midcom_show_style('pending-accounts-list-footer');
    }

    /**
     * Load the midcom_helper_datamanager2_datamanager2 instance
     *
     * @access private
     * @return boolean Indicating success
     */
    function _load_datamanager()
    {
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_account'));
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($schemadb);

        return true;
    }

    /**
     * Handle the actions for the pending account request(s)
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success
     */
    public function _handler_approve($handler_id, $args, &$data)
    {
        // Require administrator privileges
        $_MIDCOM->auth->require_admin_user();

        // Handle both several and single approval query at once
        if ($args[0] === 'multiple')
        {
            if (   !isset($_POST['persons'])
                || count($_POST['persons']) === 0)
            {
                // Give user a message of an invalid request
                $_MIDCOM->uimessages->add($this->_l10n->get('net.nehmer.account'), $this->_l10n->get('invalid query'));

                // ...and relocate back to the list of unapproved accounts
                $_MIDCOM->relocate('pending/');
                // This will exit
            }

            // Loop through all the persons requested on the previous page
            foreach ($_POST['persons'] as $guid)
            {
                $this->persons[$guid] = new midcom_db_person($guid);

                // Simple error handling check
                if (empty($this->persons[$guid]))
                {
                    unset($this->persons[$guid]);
                }
            }
        }
        else
        {
            $person = new midcom_db_person($args[0]);
            if (   !$person
                || !$person->guid)
            {
                $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The account '{$args[0]}' could not be loaded, reason: " . midcom_connection::get_error_string());
            }

            $this->persons[$person->guid] =& $person;

            $_MIDCOM->bind_view_to_object($person);
            $_MIDCOM->set_26_request_metadata(time(), $this->_topic->guid);
            $_MIDCOM->set_pagetitle($this->_l10n->get('pending approval') . ': ' . $person->rname);
        }

        // Process the query form
        $this->_process_form();

        // Set the breadcrumb path
        $this->add_breadcrumb("pending/{$args[0]}/", (!isset($person)) ? $this->_l10n->get('multiple') : $person->rname);

        // Load the Datamanager2 instance of the person object
        $this->_load_datamanager();
        $data['datamanager'] =& $this->_datamanager;

        return true;
    }

    /**
     * Show (a list of) pending approval(s) and offer a chance to write explanation on why the user was
     * not accepted
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_approve($handler_id, &$data)
    {
        midcom_show_style('pending-approval-header');

        // Show the details of each person
        foreach ($this->persons as $person)
        {
            $data['person'] =& $person;
            $data['datamanager']->autoset_storage($person);

            midcom_show_style('pending-approval-person');
        }

        // Message body for rejection message
        $data['rejected_message_subject'] = $this->_l10n->get($this->_config->get('rejected_mail_subject'));
        $data['rejected_message_body'] = $this->_l10n->get($this->_config->get('rejected_mail_body'));

        midcom_show_style('pending-approval-footer');
    }

    /**
     * Process the query form
     *
     * @access private
     */
    function _process_form()
    {
        // Handle the cancel request
        if (isset($_POST['f_cancel']))
        {
            // Show UI message
            $_MIDCOM->uimessages->show($this->_l10n->get('net.nehmer.account'), $this->_l10n->get('cancelled'));

            // Relocate
            $_MIDCOM->relocate('pending/');
            // This will exit
        }

        // Approval form sent
        if (isset($_POST['f_approve']))
        {
            foreach ($this->persons as $person)
            {
                // Get the password and activation links generated when the user was applying for approval
                $password = $person->get_parameter('net.nehmer.account', 'password');
                $activation_link = $person->get_parameter('net.nehmer.account', 'activation_link');

                // Show the status dependant user message and proceed to next
                if (net_nehmer_account_viewer::send_registration_mail($person, substr($password, 2), $activation_link, $this->_config))
                {
                    // Remove the parameter that points to a pending approval if necessary
                    $person->set_parameter('net.nehmer.account', 'require_approval', sprintf('approved by user id %s', midcom_connection::get_user()));


                    $_MIDCOM->uimessages->add($this->_l10n->get('net.nehmer.account'), sprintf($this->_l10n->get('%s, message sent to %s'), $this->_l10n_midcom->get('approved'), $person->email));
                }
                else
                {
                    $_MIDCOM->uimessages->add($this->_l10n->get('net.nehmer.account'), sprintf($this->_l10n->get('failed to send the activation message to %s'), $person->email, 'error'));
                }
            }

            // Relocate to the frontpage of accounts pending for approvate
            $_MIDCOM->relocate('pending/');
            // This will exit
        }

        // Final, confirmed reject
        if (isset($_POST['f_reject']))
        {
            // Get the possibly customized rejection message subject
            if (   isset($_POST['subject'])
                && trim($_POST['subject']))
            {
                $subject = $_POST['subject'];
            }
            else
            {
                $subject = $this->_config->get('rejected_mail_subject');
            }

            // Get the possibly customized rejection message body
            if (   isset($_POST['body'])
                && trim($_POST['body']))
            {
                $body = $_POST['body'];
            }
            else
            {
                $body = $this->_config->get('rejected_mail_body');
            }

            foreach ($this->persons as $person)
            {
                if ($this->_send_rejection_mail($person, $subject, $body))
                {
                    $_MIDCOM->uimessages->add($this->_l10n->get('net.nehmer.account'), sprintf($this->_l10n->get('%s, message sent to %s'), $this->_l10n->get('rejected'), $person->email));
                }
                else
                {
                    $_MIDCOM->uimessages->add($this->_l10n->get('net.nehmer.account'), sprintf($this->_l10n->get('failed to send the rejection message to %s'), $person->email, 'error'));
                }
            }

            // Finally relocate back to view the list
            $_MIDCOM->relocate('pending/');
            // This will exit
        }
    }

    /**
     * Send a message to tell of rejecting the privilege for the account
     *
     * @access private
     * @return boolean Indicating success
     * @param midcom_db_person $person    Person object
     * @param string $subject             Subject of the message that will be sent to the user
     * @param string $body                Body of the message that will be sent to the user
     */
    function _send_rejection_mail(&$person, $subject, $body)
    {
        $_MIDCOM->load_library('org.openpsa.mail');
        $mail = new org_openpsa_mail();
        $mail->from = $this->_config->get('activation_mail_sender');

        if (!$mail->from)
        {
            $mail->from = $person->email;
        }

        $mail->subject = $subject;
        $mail->body = $body;
        $mail->to = $person->email;

        // Get the commonly used parameters
        $parameters = net_nehmer_account_viewer::get_mail_parameters($person);

        // Convert the parameters
        $mail->subject = net_nehmer_account_viewer::parse_parameters($parameters, $mail->subject);
        $mail->body = net_nehmer_account_viewer::parse_parameters($parameters, $mail->body);

        $_MIDCOM->uimessages->add($this->_l10n->get('net.nehmer.account'), sprintf($this->_l10n->get('person %s has been rejected and deleted'), $person->name));

        // Delete the person in the end
        $person->delete();

        return $mail->send();
    }
}
?>