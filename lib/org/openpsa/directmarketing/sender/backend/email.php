<?php
/**
 * @package org.openpsa.directmarketing
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\storage\blobs;

/**
 * Campaign message sender backend for email
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_sender_backend_email implements org_openpsa_directmarketing_sender_backend
{
    private $_config = [];

    /**
     * @var org_openpsa_directmarketing_campaign_message_dba
     */
    private $_message;

    public function __construct(array $config, org_openpsa_directmarketing_campaign_message_dba $message)
    {
        //Make sure we have some backend and parameters for it defined
        if (!isset($config['mail_send_backend'])) {
            $config['mail_send_backend'] = 'try_default';
        }
        if (!isset($config['mail_send_backend_params'])) {
            $config['mail_send_backend_params'] = [];
        }
        //Check for bounce detector usage
        if (!empty($config['bounce_detector_address'])) {
            //Force bouncer as backend if default specified
            if (   empty($config['mail_send_backend'])
                || $config['mail_send_backend'] == 'try_default') {
                $config['mail_send_backend'] = 'bouncer';
            }
        }
        $this->_config = $config;
        $this->_message = $message;
    }

    /**
     * Adds necessary constraints to member QB to find valid entries
     *
     * @param midcom_core_querybuilder $qb The QB instance to work on
     */
    public function add_member_constraints($qb)
    {
        $qb->add_constraint('person.email', 'LIKE', '%@%');
    }

    /**
     * Validate results before send
     *
     * @param array $results Array of member objects
     * @return boolean Indicating success
     */
    public function check_results(array &$results)
    {
        return true;
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function get_type()
    {
        return 'email';
    }

    public function send(org_openpsa_contacts_person_dba $person, org_openpsa_directmarketing_campaign_member_dba $member, $token, $subject, $content, $from)
    {
        $mail = new org_openpsa_mail($this->_config['mail_send_backend'], $this->_config['mail_send_backend_params']);
        $mail->to = $person->email;
        $mail->subject = $subject;

        $mail->from = $from;
        if (!empty($this->_config['reply-to'])) {
            $mail->headers['Reply-To'] = $this->_config['reply-to'];
        }
        if (!empty($this->_config['bounce_detector_address'])) {
            $bounce_address = str_replace('TOKEN', $token, $this->_config['bounce_detector_address']);
            $mail->headers['Return-Path'] = $bounce_address;
        }

        //Set some List-xxx headers to avoid auto-replies and in general to be a good netizen
        $mail->headers['List-Id'] = "<{$this->_message->guid}@{$_SERVER['SERVER_NAME']}>";
        $mail->headers['List-Unsubscribe'] = '<' . $member->get_unsubscribe_url() . '>';

        debug_add('mail->from: ' . $mail->from . ', mail->to: ' . $mail->to . ', mail->subject: ' . $mail->subject);
        switch ($this->_message->orgOpenpsaObtype) {
            case org_openpsa_directmarketing_campaign_message_dba::EMAIL_TEXT:
                $mail->body = $content;
                break;
            case org_openpsa_directmarketing_campaign_message_dba::EMAIL_HTML:
                $mail->html_body = $content;
                if (!empty($this->_config['htmlemail_force_text_body'])) {
                    $mail->body = $member->personalize_message($this->_config['htmlemail_force_text_body'], $this->_message->orgOpenpsaObtype, $person);
                }
                // Allow sending only HTML body if requested
                $mail->allow_only_html = !empty($this->_config['htmlemail_onlyhtml']);
                // Skip embedding if requested
                if (empty($this->_config['htmlemail_donotembed'])) {
                    $mail->embed_images();
                }

                // Handle link detection
                if (!empty($this->_config['link_detector_address'])) {
                    $link_address = str_replace('TOKEN', $token, $this->_config['link_detector_address']);
                    $mail->html_body = $this->_insert_link_detector($mail->html_body, $link_address);
                }
                break;
            default:
                throw new midcom_error('Invalid message type, aborting');
        }

        $mail->attachments = $this->_get_attachments();

        if (!$mail->send()) {
            throw new midcom_error(sprintf(midcom::get()->i18n->get_string('FAILED to send mail to: %s, reason: %s', 'org.openpsa.directmarketing'), $mail->to, $mail->get_error_message()));
        }
        debug_add('Mail sent to: ' . $mail->to);
    }

    /**
     * Go trough datamanager types array for attachments
     *
     * @return array
     */
    private function _get_attachments()
    {
        $attachments = [];
        foreach ($this->_config['dm_storage'] as $field => $typedata) {
            if (!$typedata instanceof blobs) {
                continue;
            }
            foreach ((array) $typedata->get_value() as $attachment) {
                $att = [
                    'name' => $attachment->name,
                    'mimetype' => $attachment->mimetype
                ];
                $fp = $attachment->open('r');
                if (!$fp) {
                    //Failed to open attachment for reading, skip the file
                    continue;
                }
                /* PONDER: Should we cache the content somehow so that we only need to read it once per request ??
                 We would save some file opens at the expense of keeping the contents in memory (potentially very expensive) */
                $att['content'] = stream_get_contents($fp);
                $attachment->close();
                debug_add("adding attachment '{$attachment->name}' from field '{$field}' to attachments array");
                $attachments[] = $att;
            }
        }

        return $attachments;
    }

    /**
    * Inserts a link detector to the given HTML source. All outgoing
    * HTTP links in the source HTML are replaced with the given
    * link detector address so that the token "URL" is replaced with
    * encoded form of the original link. It is expected that the link detector
    * address points to a script that records the passed link and
    * forwards the client to the real link target.
    *
    * @param string $html the HTML source
    * @param string $address the link detector address
    * @return string HTML source with the link detector
    */
    private function _insert_link_detector($html, $address)
    {
        $address = addslashes($address);
        return preg_replace_callback(
            '/href="(http:\/\/.*?)"/i',
            function ($match) use ($address) {
                return 'href="' . str_replace("URL", rawurlencode($match[1]), $address) . '"';
            },
            $html);
    }
}
