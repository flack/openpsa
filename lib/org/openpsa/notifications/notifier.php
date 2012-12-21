<?php
/**
 * @package org.openpsa.notifications
 * @author Henri Bergius, http://bergie.iki.fi
 * @copyright Nemein Oy, http://www.nemein.com
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Class for sending notices. All component-specific notification senders should inherit from here.
 *
 * @package org.openpsa.notifications
 */
class org_openpsa_notifications_notifier extends midcom_baseclasses_components_purecode
{
    var $recipient = null;

    public function __construct($recipient)
    {
        $this->recipient = midcom_db_person::get_cached($recipient);

        parent::__construct();
    }

    /**
     * Stores the notification into database for later viewing
     */
    public function save_notification($message)
    {
        $notification = new org_openpsa_notifications_notification_dba();
        $notification->recipient = $this->recipient->id;

        if (midcom::get('auth')->user)
        {
            $user = midcom::get('auth')->user->get_storage();
            $notification->sender = $user->id;
        }

        $action_parts = explode(':', $message['action']);
        $notification->component = $action_parts[0];
        $notification->action = $action_parts[1];

        if (array_key_exists('title', $message))
        {
            $notification->title = $message['title'];
        }

        if (array_key_exists('abstract', $message))
        {
            $notification->abstract = $message['abstract'];
        }

        if (array_key_exists('content', $message))
        {
            $notification->content = $message['content'];
        }

        // TODO: Handle files

        return $notification->create();
    }

    /**
     * Sends the 'content' version of the message as email to subscribers via org.openpsa.mail
     */
    public function send_email($message)
    {
        if (empty($this->recipient->email))
        {
            return false;
        }

        $mail = new org_openpsa_mail();
        $mail->to = $this->recipient->email;

        $growl_to = $mail->to;
        if (array_key_exists('growl_to', $message))
        {
            $growl_to = $message['growl_to'];
            unset($message['growl_to']);
        }

        $sender = null;

        if (   array_key_exists('from', $message)
            && !empty($message['from']))
        {
            midcom::get('auth')->request_sudo();
            $user = midcom::get('auth')->get_user($message['from']);
            $sender =& $user->get_storage();
            midcom::get('auth')->drop_sudo();
            // Avoid double dump
            unset($message['from']);
        }

        $default_sender = $this->_config->get('default_sender');
        if (   !is_null($sender)
            && $sender->email)
        {
            $mail->from = '"' . $sender->name . '" <' . $sender->email . '>';
        }
        else if (   !is_null($default_sender)
                 && !empty($default_sender))
        {
            $mail->from = $default_sender;
        }
        else
        {
            $mail->from = '"OpenPSA Notifier" <noreply@' . $_SERVER['SERVER_NAME'] . '>';
        }

        if (array_key_exists('title', $message))
        {
            // PHP5-TODO: Must be copy by value
            $mail->subject = $message['title'];
            // Avoid double dump
            unset($message['title']);
        }
        else
        {
            $mail->subject = 'org.openpsa.notifications message (no title provided)';
        }
        if (array_key_exists('attachments', $message))
        {
            // PHP5-TODO: Must be copy by value
            $mail->attachments = $message['attachments'];
            // Do not dump attachments as content
            unset($message['attachments']);
        }

        if (array_key_exists('content', $message))
        {
            $mail->body = $message['content'];
        }
        else
        {
            // No explicit content defined, dump all keys
            foreach ($message as $key => $value)
            {
                // TODO (nice-to-have): RFC "fold" the value
                $mail->body .= "{$key}: {$value}\n";
            }
        }

        $ret = $mail->send();
        if (!$ret)
        {
            debug_add("failed to send notification email to {$mail->to}, reason: " . $mail->get_error_message(), MIDCOM_LOG_WARN);
        }
        else
        {
            midcom::get('uimessages')->add(midcom::get('i18n')->get_string('org.openpsa.notifications', 'org.openpsa.notifications'), sprintf(midcom::get('i18n')->get_string('notification sent to %s', 'org.openpsa.notifications'), $growl_to));
        }
        return $ret;
    }

    /**
     * Sends the 'abstract' version of the message as NetGrowl message to subscribers via org.openpsa.smslib
     */
    public function send_growl($message)
    {
        // TODO: Implement
        return false;
    }

    /**
     * Sends the 'abstract' version of the message as SMS to subscribers via org.openpsa.smslib
     */
    public function send_sms($message)
    {
        // TODO: Implement
        return false;
    }

    /**
     * Sends the 'abstract' version of the message as a Jabber message to subscribers via org.openpsa.jabber
     */
    public function send_xmpp($message)
    {
        // TODO: Implement
        return false;
    }
}
?>