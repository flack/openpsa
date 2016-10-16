<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Lost Password handler class
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_handler_lostpassword extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_nullstorage
{
    /**
     * The mode we're using (by username, by email, by username and email or none)
     *
     * @var string
     */
    private $_mode;

    /**
     * The controller used to display the password reset dialog.
     *
     * @var midcom_helper_datamanager2_controller
     */
    private $_controller;

    /**
     * This is true if we did successfully change the password. It will then display a simple
     * password-changed-successfully response.
     *
     * @var boolean
     */
    private $_success = false;

    /**
     * The localized processing message
     *
     * @var string
     */
    private $_processing_msg;

    /**
     * The raw processing message
     *
     * @var string
     */
    private $_processing_msg_raw;

    public function load_schemadb()
    {
        return midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_lostpassword'));
    }

    public function get_schema_name()
    {
        return $this->_mode;
    }

    /**
     * Prepare the request data with all computed values.
     * A special case is the visible_data array, which maps field names
     * to prepared values, which can be used in display directly. The
     * information returned is already HTML escaped.
     */
    private function _prepare_request_data()
    {
        $this->_request_data['controller'] = $this->_controller;
        $this->_request_data['processing_msg'] = $this->_processing_msg;
        $this->_request_data['processing_msg_raw'] = $this->_processing_msg_raw;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_lostpassword($handler_id, array $args, array &$data)
    {
        $this->_mode = $this->_config->get('lostpassword_mode');
        if ($this->_mode == 'none')
        {
            throw new midcom_error_notfound('This feature is disabled');
        }

        $this->_controller = $this->get_controller('nullstorage');

        switch ($this->_controller->process_form())
        {
            case 'save':
                $this->_reset_password();
                $this->_processing_msg = $this->_l10n->get('password reset, mail sent.');
                $this->_processing_msg_raw = 'password reset, mail sent.';
                $this->_success = true;

                break;

            case 'cancel':
                return new midcom_response_relocate('');
        }
        $this->_prepare_request_data();

        midcom::get()->head->set_pagetitle($this->_l10n->get('lost password'));
    }

    /**
     * Reset the password to a randomly generated one.
     */
    private function _reset_password()
    {
        if (! midcom::get()->auth->request_sudo($this->_component))
        {
            throw new midcom_error('Failed to request sudo privileges.');
        }

        $qb = midcom_db_person::new_query_builder();
        if (array_key_exists('username', $this->_controller->datamanager->types))
        {
            $user = midcom::get()->auth->get_user_by_name($this->_controller->datamanager->types['username']->value);
            if (!$user)
            {
                midcom::get()->auth->drop_sudo();
                throw new midcom_error("Cannot find user. For some reason the QuickForm validation failed.");
            }
            $qb->add_constraint('guid', '=', $user->guid);
        }
        if (array_key_exists('email', $this->_controller->datamanager->types))
        {
            $qb->add_constraint('email', '=', $this->_controller->datamanager->types['email']->value);
        }
        $results = $qb->execute();

        if (sizeof($results) != 1)
        {
            midcom::get()->auth->drop_sudo();
            throw new midcom_error("Cannot find user. For some reason the QuickForm validation failed.");
        }
        $person = $results[0];
        $account = new midcom_core_account($person);

        // Generate a random password
        $length = max(8, $this->_config->get('password_minlength'));
        $password = org_openpsa_user_accounthelper::generate_password($length);
        $account->set_password($password);
        if (!$account->save())
        {
            midcom::get()->auth->drop_sudo();
            throw new midcom_error("Could not update the password: " . midcom_connection::get_error_string());
        }

        midcom::get()->auth->drop_sudo();

        $this->_send_reset_mail($person, $password);
    }

    /**
     * This is a simple function which generates and sends a password reset mail.
     *
     * @param midcom_db_person $person The newly created person account.
     */
    private function _send_reset_mail($person, $password)
    {
        $from = $this->_config->get('lostpassword_reset_mail_sender');
        if (! $from)
        {
            $from = $person->email;
        }

        $parameters = array
        (
            'PERSON' => $person,
            'PASSWORD' => $password,
        );

        $mail = new org_openpsa_mail();
        $mail->from = $from;
        $mail->to = $person->email;
        $mail->subject = $this->_config->get('lostpassword_reset_mail_subject');
        $mail->body = $this->_config->get('lostpassword_reset_mail_body');
        $mail->parameters = $parameters;

        $mail->send();
    }

    /**
     * Shows either the username change dialog or a success message.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_lostpassword($handler_id, array &$data)
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
}
