<?php
/**
 * @package org.openpsa.user
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\storage\container\container;
use Symfony\Component\HttpFoundation\Request;

/**
 * Lost Password handler class
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_handler_lostpassword extends midcom_baseclasses_components_handler
{
    public function _handler_lostpassword(Request $request, array &$data)
    {
        $mode = $this->_config->get('lostpassword_mode');
        if ($mode == 'none') {
            throw new midcom_error_notfound('This feature is disabled');
        }

        $data['controller'] = datamanager::from_schemadb($this->_config->get('schemadb_lostpassword'))
            ->set_storage(schemaname: $mode)
            ->get_controller();

        midcom::get()->head->set_pagetitle($this->_l10n->get('lost password'));

        switch ($data['controller']->handle($request)) {
            case 'save':
                $this->_reset_password($data['controller']->get_form_values());
                $data['processing_msg'] = $this->_l10n->get('password reset, mail sent.');
                return $this->show('show-lostpassword-ok');

            case 'cancel':
                return new midcom_response_relocate('');
        }

        return $this->show('show-lostpassword');
    }

    /**
     * Reset the password to a randomly generated one.
     */
    private function _reset_password(container $formdata)
    {
        if (!midcom::get()->auth->request_sudo($this->_component)) {
            throw new midcom_error('Failed to request sudo privileges.');
        }

        $qb = midcom_db_person::new_query_builder();
        if (isset($formdata['username'])) {
            $user = midcom::get()->auth->get_user_by_name($formdata['username']);
            if (!$user) {
                midcom::get()->auth->drop_sudo();
                throw new midcom_error("Cannot find user");
            }
            $qb->add_constraint('guid', '=', $user->guid);
        }
        if (isset($formdata['email'])) {
            $qb->add_constraint('email', '=', $formdata['email']);
        }
        $results = $qb->execute();

        if (count($results) != 1) {
            midcom::get()->auth->drop_sudo();
            throw new midcom_error("Cannot find user");
        }
        $person = $results[0];
        $account = new midcom_core_account($person);

        // Generate a random password
        $length = max(8, $this->_config->get('min_password_length'));
        $password = midgard_admin_user_plugin::generate_password($length);
        $account->set_password($password);
        if (!$account->save()) {
            midcom::get()->auth->drop_sudo();
            throw new midcom_error("Could not update the password: " . midcom_connection::get_error_string());
        }

        midcom::get()->auth->drop_sudo();

        $this->_send_reset_mail($person, $password);
    }

    /**
     * This is a simple function which generates and sends a password reset mail.
     */
    private function _send_reset_mail(midcom_db_person $person, string $password)
    {
        $from = $this->_config->get('lostpassword_reset_mail_sender') ?: $person->email;

        $parameters = [
            'PERSON' => $person,
            'PASSWORD' => $password,
        ];

        $mail = new org_openpsa_mail();
        $mail->from = $from;
        $mail->to = $person->email;
        $mail->subject = $this->_config->get('lostpassword_reset_mail_subject');
        $mail->body = $this->_config->get('lostpassword_reset_mail_body');
        $mail->parameters = $parameters;

        $mail->send();
    }
}
