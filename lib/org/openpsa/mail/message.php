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
        foreach ($headers as $name => $value)
        {
            if (array_key_exists(strtolower($name), $headers_setter_map))
            {
                $setter = $headers_setter_map[strtolower($name)];
                $this->_message->$setter($value);
            }
            else
            {
                // header already exists => just set a new value (avoid duplicated MIME-Version)
                if ($msg_headers->get($name))
                {
                    $msg_headers->get($name)->setValue($value);
                }
                else
                {
                    $msg_headers->addTextHeader($name, $value);
                }
            }
        }

        // somehow we need to set the body after the headers...
        if (!empty($this->_html_body))
        {
            $this->_message->setBody($this->_html_body, 'text/html');
            $this->_message->addPart($this->_body, 'text/plain');
        }
        else
        {
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
        if (   !isset($this->_headers['Content-Type'])
            || $this->_headers['Content-Type'] == null)
        {
            $this->_headers['Content-Type'] = "text/plain; charset={$this->_encoding}";
        }
        // Set Mime-version if not set already (done this way to accommodate for various typings
        $mime_header = false;

        //Make sure we don't send any empty headers
        reset ($this->_headers);
        foreach ($this->_headers as $header => $value)
        {
            if (empty($value))
            {
                debug_add("Header '{$header}' has empty value, removing");
                unset ($this->_headers[$header]);
                continue;
            }
            if (strtolower($header) == 'mime-version')
            {
                $mime_header = $header;
            }
            else if (strtolower($header) == 'subject')
            {
                // Encode subject (if necessary)
                $this->_headers[$header] = $this->_encode_quoted_printable($value);
            }
            else if (   strtolower($header) == 'from'
                     || strtolower($header) == 'reply-to'
                     || strtolower($header) == 'to')
            {
                $this->_headers[$header] = $this->_encode_address_field($value);
            }

            if (is_array($value))
            {
                //This is most probably an address header, like To or Cc
                debug_add('Header ' . $header . ' is in array format. Converting to comma-separated string');
                $value = implode(', ', $value);
                $this->_headers[$header] = $value;
            }

            $value_trimmed = trim($value);
            if ($value_trimmed != $value)
            {
                debug_add("Header '{$header}' has whitespace around its value, rewriting from\n===\n{$value}\n===\nto\n===\n{$value_trimmed}\n===\n");
                $this->_headers[$header] = $value_trimmed;
            }
        }

        if (   $mime_header === false
            || $this->_headers[$mime_header] == null)
        {
            if ($mime_header === false)
            {
                $this->_headers['MIME-Version'] = '1.0';
            }
            else
            {
                $this->_headers[$mime_header] = '1.0';
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
        if ($do_image_embedding)
        {
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

        foreach ($matches["whole"] as $key => $match)
        {
            $location = $matches["location"][$key];
            // uri is fully qualified
            if ($matches['proto'][$key])
            {
                $uri = $matches["uri"][$key];
            }
            // uri is relative
            else if (preg_match('/^\//', $location))
            {
                $uri = 'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $location;
                $port = $_SERVER['SERVER_PORT'];
                // if its a default port..
                if ($port == 443 || $port == 80)
                {
                    $proto = ($port == 443) ? "https" : "http";
                    $uri = $proto . "://" . $_SERVER['SERVER_NAME'] . $location;
                }
            }

            // replace src by swiftmailer embedded image
            $repl = $this->_message->embed(Swift_Image::fromPath($uri));
            $new_html = str_replace($location, $repl, $match);
            $this->_html_body = str_replace($match, $new_html, $this->_html_body);
        }
    }


    private function _process_attachments($attachments)
    {
        foreach ($attachments as $att)
        {
            if (!isset($att['mimetype']) || $att['mimetype'] == null)
            {
                $att['mimetype'] = "application/octet-stream";
            }

            $swift_att = false;
            // we got a file path
            if (isset($att['file']) && strlen($att['file']) > 0)
            {
                $swift_att = Swift_Attachment::fromPath($att['file'], $att['mimetype']);
            }
            // we got the contents (bytes)
            else if (isset($att['content']) && strlen($att['content']) > 0)
            {
                $swift_att = Swift_Attachment::newInstance($att['content'], $att['name'], $att['mimetype']);
            }

            if ($swift_att)
            {
                $this->_message->attach($swift_att);
            }
        }
    }

    /**
     * Helper function to work around a problem where some PEAR mail backends (or versions)
     * trip over special characters in addresses
     *
     * @param string $value The value to encode
     * @return string the encoded value
     */
    private function _encode_address_field($value)
    {
        if (is_array($value))
        {
            array_walk($value, array($this, '_encode_address_field'));
            return $value;
        }
        if (strpos($value, '<'))
        {
            $name = substr($value, 0, strpos($value, '<'));
            $name = preg_replace('/^\s*"/', '', $name);
            $name = preg_replace('/"\s*$/', '', $name);
            $address = substr($value, strpos($value, '<') + 1);
            $address = substr($address, 0, strlen($address) - 1);
            $value = array($address => $name);
        }
        return $value;
    }

    /**
     * Quoted-Printable encoding for message headers if necessary
     *
     * @todo See if this can be replaced by quoted_printable_encode once we go to 5.3
     */
    private function _encode_quoted_printable($subject)
    {
        preg_match_all("/[^\x21-\x39\x41-\x7e]/", $subject, $matches);
        if (   count ($matches[0]) > 0
            && !stristr($subject, '=?' . strtoupper($this->_encoding) . '?Q?'))
        {
            // Sort the results to make sure '=' gets encoded first (otherwise there will be double-encodes...)
            usort($matches[0], array($this, '_sort_encode_subject'));
            debug_print_r("matches[0]", $matches);
            $cache = array();
            $newSubj = $subject;
            foreach ($matches[0] as $char)
            {
                $hex = str_pad(strtoupper(dechex(ord($char))), 2, '0', STR_PAD_LEFT);
                if (isset($cache[$hex]))
                {
                    continue;
                }
                $code = '=' . $hex;
                debug_add("encoding  '{$char}' to '{$code}'");
                $newSubj = str_replace($char, $code, $newSubj);
                $cache[$hex] = true;
            }
            $subject = '=?' . strtoupper($this->_encoding) . '?Q?' . $newSubj . '?=';
        }
        return $subject;
    }

    private function _sort_encode_subject($a, $b)
    {
        if ($a == '=')
        {
            return -1;
        }
        if ($b == '=')
        {
            return 1;
        }
        $aord = ord($a);
        $bord = ord($b);
        if ($aord < $bord)
        {
            return -1;
        }
        if ($aord > $bord)
        {
            return 1;
        }
        return 0;
    }
}
?>