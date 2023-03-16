<?php
/**
 * @package org.openpsa.mail
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\MimeTypes;

/**
 * Wrapper class for emails
 *
 * @package org.openpsa.mail
 */
class org_openpsa_mail_message
{
    private $_to;

    private string $_encoding;

    private array $_headers;

    private $_body;
    private $_html_body;

    private Email $_message;

    public function __construct($to, array $headers, string $encoding)
    {
        $this->_to = $to;
        $this->_headers = $headers;
        $this->_encoding = $encoding;

        $this->_message = new Email;
    }

    public function get_recipients()
    {
        return $this->_to;
    }

    public function get_message() : Email
    {
        // set headers
        $msg_headers = $this->_message->getHeaders();

        foreach ($this->get_headers() as $name => $value) {
            if (is_string($value) && in_array(strtolower($name), ["from", "to", "cc", "bcc", "reply-to"])) {
                $value = [$value];
            }
            $msg_headers->addHeader($name, $value);
        }

        // somehow we need to set the body after the headers...
        if (!empty($this->_html_body)) {
            $this->_message->html($this->_html_body);
        }
        $this->_message->text($this->_body);

        return $this->_message;
    }

    public function set_header_field(string $name, $value)
    {
        $this->_headers[$name] = $value;
    }

    public function get_headers() : array
    {
        reset($this->_headers);
        foreach ($this->_headers as $header => $value) {
            if (is_string($value)) {
                $this->_headers[$header] = trim($value);
            }
        }

        return $this->_headers;
    }

    public function get_body()
    {
        return $this->_body;
    }

    public function set_body(string $body)
    {
        $this->_body = $body;
        $this->_html_body = null;
    }

    public function set_html_body(string $body, string $altBody, array $attachments, bool $do_image_embedding)
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

        $matches = [
            "whole" => $matches_src[0],
            "uri" => $matches_src[3],
            "proto" => $matches_src[4],
            "location" => $matches_src[6]
        ];

        foreach ($matches["whole"] as $key => $match) {
            $location = $matches["location"][$key];
            // uri is fully qualified
            if ($matches['proto'][$key]) {
                $uri = $matches["uri"][$key];
            }
            // uri is absolute
            elseif (str_starts_with($location, '/')) {
                $uri = midcom::get()->get_host_name() . $location;
            } else {
                debug_add('No usable uri found, skipping embed', MIDCOM_LOG_WARN);
                continue;
            }

            // replace src by embedded image
            $name = basename($uri);
            $mimetype = null;
            if ($ext = pathinfo($name, PATHINFO_EXTENSION)) {
                $mimetype = (new MimeTypes)->getMimeTypes($ext)[0] ?? null;
            }

            $part = (new DataPart(fopen($uri, 'r'), $name, $mimetype))
                ->asInline();
            $this->_message->attachPart($part);

            $new_html = str_replace($location, 'cid:' . $part->getContentId(), $match);
            $this->_html_body = str_replace($match, $new_html, $this->_html_body);
        }
    }

    private function _process_attachments(array $attachments)
    {
        foreach ($attachments as $att) {
            // we got a file path
            if (!empty($att['file'])) {
                $this->_message->attachFromPath($att['file'], $att['name'] ?? basename($att['file']), $att['mimetype'] ?? null);
            }
            // we got the contents (bytes)
            elseif (!empty($att['content'])) {
                $this->_message->attach($att['content'], $att['name'], $att['mimetype'] ?? null);
            }
        }
    }
}
