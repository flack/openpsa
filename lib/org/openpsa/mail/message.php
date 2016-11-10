<?php
/**
 * @package org.openpsa.mail
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Wrapper class for emails
 *
 * @package org.openpsa.mail
 */
class org_openpsa_mail_message
{
    private $_to;

    private $_encoding;

    private $_headers;

    private $_body;
    private $_html_body = null;

    /**
     *
     * @var Swift_Message
     */
    private $_message;

    public function __construct($to, array $headers, $encoding)
    {
        $this->_to = $this->_encode_address_field($to);
        $this->_headers = $headers;
        $this->_encoding = $encoding;

        $this->_message = Swift_Message::newInstance('');
    }

    public function get_recipients()
    {
        return $this->_to;
    }

    public function get_message()
    {
        // set headers
        $headers_setter_map = array(
            "content-type" => "setContentType",
            "content-description" => "setDescription",
            "from" => "setFrom",
            "to" => "setTo",
            "cc" => "setCc",
            "bcc" => "setBcc",
            "reply-to" => "setReplyTo",
            "subject" => "setSubject",
            "date" => "setDate",
            "return-path" => "setReturnPath"
        );

        // map headers we got to swift setter methods
        $msg_headers = $this->_message->getHeaders();
        $headers = $this->get_headers();
        foreach ($headers as $name => $value) {
            if (array_key_exists(strtolower($name), $headers_setter_map)) {
                $setter = $headers_setter_map[strtolower($name)];
                $this->_message->$setter($value);
            } else {
                // header already exists => just set a new value
                if ($msg_headers->has($name)) {
                    $msg_headers->get($name)->setValue($value);
                } else {
                    $msg_headers->addTextHeader($name, $value);
                }
            }
        }

        // somehow we need to set the body after the headers...
        if (!empty($this->_html_body)) {
            $this->_message->setBody($this->_html_body, 'text/html');
            $this->_message->addPart($this->_body, 'text/plain');
        } else {
            $this->_message->setBody($this->_body, 'text/plain');
        }

        return $this->_message;
    }

    public function set_header_field($name, $value)
    {
        $this->_headers[$name] = $value;
    }

    public function get_headers()
    {
        if (empty($this->_headers['Content-Type'])) {
            $this->_headers['Content-Type'] = "text/plain; charset={$this->_encoding}";
        }

        reset ($this->_headers);
        foreach ($this->_headers as $header => $value) {
            if (is_string($value)) {
                $this->_headers[$header] = trim($value);
            }
            if (   strtolower($header) == 'from'
                || strtolower($header) == 'reply-to'
                || strtolower($header) == 'to') {
                $this->_headers[$header] = $this->_encode_address_field($value);
            }
        }

        return $this->_headers;
    }

    public function get_body()
    {
        return $this->_body;
    }

    public function set_body($body)
    {
        $this->_body = $body;
        $this->_html_body = null;
    }

    /**
     *
     * @param string $body the html body
     * @param string $altBody the alternative (text) body
     * @param array $attachments
     * @param boolean $do_image_embedding
     */
    public function set_html_body($body, $altBody, $attachments, $do_image_embedding)
    {
        $this->_body = $altBody;
        $this->_html_body = $body;

        // adjust html body
        if ($do_image_embedding) {
            $this->_embed_images();
        }

        // process attachments
        $this->_process_attachments($attachments);
    }

    private function _embed_images()
    {
        // anything with SRC = "" something in it (images etc)
        $regExp_src = "/(src|background)=([\"'�])(((https?|ftp):\/\/)?(.*?))\\2/i";
        preg_match_all($regExp_src, $this->_html_body, $matches_src);
        debug_print_r("matches_src:", $matches_src);

        $matches = array(
            "whole" => $matches_src[0],
            "uri" => $matches_src[3],
            "proto" => $matches_src[4],
            "location" => $matches_src[6]
        );

        foreach ($matches["whole"] as $key => $match) {
            $location = $matches["location"][$key];
            // uri is fully qualified
            if ($matches['proto'][$key]) {
                $uri = $matches["uri"][$key];
            }
            // uri is relative
            elseif (preg_match('/^\//', $location)) {
                $uri = midcom::get()->get_host_name() . $location;
            }

            // replace src by swiftmailer embedded image
            $repl = $this->_message->embed(Swift_Image::fromPath($uri));
            $new_html = str_replace($location, $repl, $match);
            $this->_html_body = str_replace($match, $new_html, $this->_html_body);
        }
    }

    private function _process_attachments($attachments)
    {
        foreach ($attachments as $att) {
            if (empty($att['mimetype'])) {
                $att['mimetype'] = "application/octet-stream";
            }

            $swift_att = false;
            // we got a file path
            if (isset($att['file']) && strlen($att['file']) > 0) {
                $swift_att = Swift_Attachment::fromPath($att['file'], $att['mimetype']);
            }
            // we got the contents (bytes)
            elseif (isset($att['content']) && strlen($att['content']) > 0) {
                $swift_att = Swift_Attachment::newInstance($att['content'], $att['name'], $att['mimetype']);
            }

            if ($swift_att) {
                $this->_message->attach($swift_att);
            }
        }
    }

    /**
     * Helper function that provides backwards compatibility
     * to addresses specified in a "Name <email@addre.ss>" format
     *
     * @param string $value The value to encode
     * @return mixed the encoded value
     */
    private function _encode_address_field($value)
    {
        if (is_array($value)) {
            array_walk($value, array($this, '_encode_address_field'));
            return $value;
        }
        if (strpos($value, '<')) {
            $name = substr($value, 0, strpos($value, '<'));
            $name = preg_replace('/^\s*"/', '', $name);
            $name = preg_replace('/"\s*$/', '', $name);
            $address = substr($value, strpos($value, '<') + 1);
            $address = substr($address, 0, strlen($address) - 1);
            $value = array($address => $name);
        }
        return $value;
    }
}
