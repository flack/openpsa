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

    /**
     * Mail_mime holder
     *
     * @var Mail_mime
     */
    private $__mime;

    public function __construct($to, array $headers, $encoding)
    {
        $this->_to = $to;
        $this->_headers = $headers;
        $this->_encoding = $encoding;
    }

    /**
     * Merges all email recipients into a comma-separated string
     *
     * @todo Support arrays of Cc/Bcc addresses as well
     */
    public function get_recipients()
    {
        if (is_string($this->_to))
        {
            $addresses = array($this->_to);
        }
        else
        {
            $addresses = $this->_to;
        }
        if (!empty($this->_headers['Cc']))
        {
            $addresses[] = $this->_headers['Cc'];
        }
        if (!empty($this->_headers['Bcc']))
        {
            $addresses[] = $this->_headers['Bcc'];
        }
        return implode(', ', $addresses);
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
                $this->_headers[$header] = $this->_encode_subject($value);
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
                $this->_headers['Mime-version'] = '1.0';
            }
            else
            {
                $this->_headers[$mime_header] = '1.0';
            }
        }

        //TODO: Encode from, cc and to if necessary

        return $this->_headers;
    }

    public function get_body()
    {
        return $this->_body;
    }

    public function set_body($body)
    {
        $this->_body = $body;
    }

    public function set_mime_body($text_body, $html_body, $attachments, $embeds)
    {
        if (!class_exists('Mail_mime'))
        {
            @include_once('Mail/mime.php');
        }

        if (!class_exists('Mail_mime'))
        {
            debug_add('Mail_mime does not exist, setting text body and aborting', MIDCOM_LOG_WARN);
            $this->_body = $text_body;
            return false;
        }

        $this->__mime = new Mail_mime("\n");

        $this->__mime->_build_params['html_charset'] = strtoupper($this->_encoding);
        $this->__mime->_build_params['text_charset'] = strtoupper($this->_encoding);
        $this->__mime->_build_params['head_charset'] = strtoupper($this->_encoding);
        $this->__mime->_build_params['text_encoding'] = '8bit';

        reset($this->__mime);

        if (strlen($html_body) > 0)
        {
           $this->__mime->setHTMLBody($html_body);
        }
        if (strlen($text_body) > 0)
        {
           $this->__mime->setTxtBody($text_body);
        }
        if (!empty($attachments))
        {
            $this->_process_attachments($attachments, 'addAttachment');
        }
        if (!empty($embeds))
        {
            $this->_process_attachments($embeds, 'addHTMLImage');
        }
        $this->_body = $this->__mime->get();

        $this->_headers = $this->__mime->headers($this->_headers);
        // some MTAs manage to mangle multiline headers (RFC "folded"),
        // here we make sure at least the content type is in single line
        $this->_headers['Content-Type'] = preg_replace('/\s+/', ' ', $this->_headers['Content-Type']);
    }

    private function _process_attachments($attachments, $method)
    {
        reset($attachments);
        while (list ($k, $att) = each ($attachments))
        {
            if (!isset($att['mimetype']) || $att['mimetype'] == null)
            {
                $att['mimetype'] = "application/octet-stream";
            }

            if (isset($att['file']) && strlen($att['file']) > 0)
            {
                $aRet = $this->__mime->$method($att['file'], $att['mimetype'], $att['name'], true);
            }
            else if (isset($att['content']) && strlen($att['content']) > 0)
            {
                $aRet = $this->__mime->$method($att['content'], $att['mimetype'], $att['name'], false);
            }

            if ($aRet !== true)
            {
                debug_print_r($method . " failed on attachment " . $att['name'] . " PEAR output:", $aRet);
            }
        }
    }

    /**
     * Quoted-Printable encoding for message subject if necessary
     */
    private function _encode_subject($subject)
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
            while (list ($k, $char) = each ($matches[0]))
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