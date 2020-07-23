<?php
/**
 * @package org.openpsa.notifications
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Sends the 'content' version of the message as email to subscribers via org.openpsa.mail
 *
 * @package org.openpsa.notifications
 */
class org_openpsa_notifications_notifier_email implements org_openpsa_notifications_notifier
{
    public function send(midcom_db_person $recipient, array $message)
    {
        if (empty($recipient->email)) {
            return false;
        }

        $mail = new org_openpsa_mail();
        $mail->to = $recipient->email;

        $sender = null;

        if (!empty($message['from'])) {
            midcom::get()->auth->request_sudo('org.openpsa.notifications');
            $user = midcom::get()->auth->get_user($message['from']);
            $sender = $user->get_storage();
            midcom::get()->auth->drop_sudo();
        }

        if (!empty($sender->email)) {
            $mail->from = '"' . $sender->name . '" <' . $sender->email . '>';
        } else {
            $config = midcom_baseclasses_components_configuration::get('org.openpsa.notifications', 'config');
            $mail->from = $config->get('default_sender') ?: '"OpenPSA Notifier" <noreply@' . $_SERVER['SERVER_NAME'] . '>';
        }

        $mail->subject = $message['title'] ??  'org.openpsa.notifications message (no title provided)';
        if (array_key_exists('attachments', $message)) {
            $mail->attachments = $message['attachments'];
        }

        if (!array_key_exists('content', $message)) {
            $message['content'] = '';
            // No explicit content defined, dump all keys
            foreach ($message as $key => $value) {
                // TODO (nice-to-have): RFC "fold" the value
                if (!in_array($key, ['title', 'attachments', 'from'])) {
                    $message['content'] .= "{$key}: {$value}\n";
                }
            }
        }

        $mail->body = $message['content'];

        $ret = $mail->send();
        if (!$ret) {
            debug_add("failed to send notification email to {$mail->to}, reason: " . $mail->get_error_message(), MIDCOM_LOG_WARN);
        }
        return $ret;
    }
}
