<?php
/**
 * @package org.openpsa.mail
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Class for handling email sending
 *
 * <b>Sending Mails</b>
 *
 * Currently, the engine will send the emails through an autodetected transport, which
 * can be either SMTP, Sendmail or PHP's mail() function (in that order).
 *
 * <b>Example usage code</b>
 *
 * <code>
 * $mail = new org_openpsa_mail();
 *
 * $mail->from = 'noreply@openpsa2.org';
 * $mail->subject = $this->_config->get('mail_from');
 * $mail->body = $this->_config->get('mail_body');
 * $mail->to = $this->_person->email;
 *
 * if (!$mail->send())
 * {
 *     debug_add("Email could not be sent: " . $mail->get_error_string(), MIDCOM_LOG_WARN);
 * }
 * </code>
 *
 * @property mixed $bcc BCC address(es)
 * @property mixed $cc CC address(es)
 * @property mixed $from From address(es)
 * @property string $subject Subject line
 * @property mixed $to To address(es)
 * @package org.openpsa.mail
 */
class org_openpsa_mail
{
    use midcom_baseclasses_components_base {__get as base__get;}

    /**
     * Text body
     */
    public string $body = '';

    /**
     * key is header name, value is header data
     */
    public array $headers = [
        'Subject' => null,
        'From' => null,
        'To' => null,
        'Cc' => null,
        'Bcc' => null,
    ];

    /**
     * HTML body (of MIME/multipart message)
     */
    public string $html_body = '';

    /**
     * The parameters to use for the Mail template.
     */
    public array $parameters = [];

    /**
     * Primary keys are int, secondary keys for decoded array are:
     *
     * 'name'     Filename
     * 'file'     File path. Alternatively, content can be passed in
     * 'content'  File contents
     * 'mimetype' MIME type
     */
    public array $attachments = [];

    /**
     * Character encoding in which the texts etc are
     */
    public string $encoding;

    /**
     * Allow to send only HTML body
     */
    public bool $allow_only_html = false;

    /**
     * shall embed_images be called by message class?
     */
    private bool $_do_image_embedding = false;

    private org_openpsa_mail_backend $_backend;

    public function __construct(string $backend = 'try_default', array $backend_params = [])
    {
        $this->_component = 'org.openpsa.mail';
        $this->encoding = $this->_i18n->get_current_charset();

        $this->_backend = org_openpsa_mail_backend::get($backend, $backend_params);

        $this->headers['X-Originating-IP'] = '[' . $_SERVER['REMOTE_ADDR'] . ']';
        $this->headers['X-Mailer'] = "PHP/" . PHP_VERSION . ' /OpenPSA/' . midcom::VERSION . '/' . get_class($this->_backend);
    }

    /**
     * Make it possible to get header values via $mail->to and the like
     */
    public function __get($name)
    {
        $name = ucfirst($name);
        if (array_key_exists($name, $this->headers)) {
            return $this->headers[$name];
        }

        return $this->base__get($name);
    }

    /**
     * Make it possible to set header values via $mail->to and the like
     */
    public function __set($name, $value)
    {
        $name = ucfirst($name);
        if (array_key_exists($name, $this->headers)) {
            $this->headers[$name] = $value;
        }
    }

    /**
     * Tries to convert HTML to plaintext
     */
    private function html2text(string $html) : string
    {
        // strip all tags except br
        $text = strip_tags($html, '<br>');

        // Decode entities
        $text = html_entity_decode($text, ENT_QUOTES);

        $converters = [
            '/\x0a\x0d|\x0d\x0a|\x0d/' => "\n", // Convert various newlines to unix ones
            "/<br\s*\\/?>/i" => "\n", // convert <br/> tags to newlines
            "/[ \t\f]+$/m" => '', // Trim whitespace from end of lines
            "/^[ \t\f]+/m" => '', // Trim whitespace from beginning of lines
            "/[ \t\f]+/" => ' ', // Convert multiple concurrent spaces to one
            "/\n{3,}/" => "\n\n", // Strip extra linebreaks
        ];
        $text = preg_replace(array_keys($converters), array_values($converters), $text);

        return trim($text);
    }

    /**
     * Prepares message for sending
     *
     * Calls MIME etc encodings as necessary.
     */
    private function _prepare_message() : org_openpsa_mail_message
    {
        if (!empty($this->parameters)) {
            $template_helper = new org_openpsa_mail_template($this->parameters);
            $this->headers['Subject'] = $template_helper->parse($this->headers['Subject']);
            $this->body = $template_helper->parse($this->body);
            $this->html_body = $template_helper->parse($this->html_body);
        }

        //Try to translate HTML-only body to plaintext as well
        if (empty($this->body) && !empty($this->html_body) && !$this->allow_only_html) {
            $this->body = $this->html2text($this->html_body);
        }

        $message = new org_openpsa_mail_message($this->to, array_filter($this->headers), $this->encoding);

        //Check whether it's necessary to initialize MIME
        if (!empty($this->html_body) || !empty($this->attachments)) {
            $message->set_html_body($this->html_body, $this->body, $this->attachments, $this->_do_image_embedding);
        } else {
            $message->set_body($this->body);
        }

        return $message;
    }

    /**
     * Sends the email
     */
    public function send()
    {
        // prepare mail for sending
        $message = $this->_prepare_message();

        $ret = $this->_backend->send($message);
        if (!$ret) {
            debug_add('Mail sending failed: ' . $this->_backend->get_error_message(), MIDCOM_LOG_ERROR);
        }
        return $ret;
    }

    public function embed_images()
    {
        $this->_do_image_embedding = true;
    }

    /**
     * Get error message from mail class
     */
    public function get_error_message() : string
    {
        return $this->_backend->get_error_message();
    }
}
