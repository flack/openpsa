<?php
/**
 * @package org.openpsa.mail
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Class for handling email encode/decode and sending
 *
 * Gracefully degrades in functionality if certain PEAR libraries are
 * not available.
 *
 * @package org.openpsa.mail
 */
class org_openpsa_mail_decoder extends org_openpsa_mail
{
    /**
     * Mail_mimeDecode holder
     *
     * @var Mail_mimeDecode
     */
    private $__mime;

    /**
     * Original encoding of the message
     *
     * @var string
     */
    private $__orig_encoding = '';

    /**
     * Used in _part_decode
     *
     * @var boolean
     */
    private $__textBodyFound;

    /**
     * Used in _part_decode
     *
     * @var boolean
     */
    private $__htmlBodyFound;

    /**
     * An email to decode
     *
     * @var string
     */
    private $_message;

    public function __construct()
    {
        $this->_component = 'org.openpsa.mail';
    }

    /**
     * Decodes MIME content from $this->body
     */
    function mime_decode($message)
    {
        if (!class_exists('Mail_mimeDecode'))
        {
            @include_once('Mail/mimeDecode.php');
        }

        $this->_message = $message;

        $this->headers['User-Agent'] = 'Midgard/' . substr(mgd_version(), 0, 4);
        $this->headers['X-Originating-Ip'] = $_SERVER['REMOTE_ADDR'];

        $this->encoding = $this->_i18n->get_current_charset();

        if (!class_exists('Mail_mimeDecode'))
        {
            debug_add('Cannot decode without Mail_mimeDecode, aborting', MIDCOM_LOG_ERROR);
            return false;
        }

        // Make sure we only have NL linebreaks
        $this->_message = preg_replace("/\n\r|\r\n|\r/", "\n", $this->_message);

        $this->_check_boundary();

        $args = array();
        $args['include_bodies'] = true;
        $args['decode_bodies'] = true;
        $args['decode_headers'] = true;
        $args['crlf'] = "\n";
        $args['input'] = $this->_message;

        $decoder = new Mail_mimeDecode($this->_message);
        $this->__mime = $decoder->decode($args);

        if (is_a($this->__mime, 'pear_error'))
        {
            return false;
        }

        // ucwords all header keys
        if (is_array($this->__mime->headers))
        {
            reset ($this->__mime->headers);
            foreach ($this->__mime->headers as $k => $v)
            {
                $this->headers[str_replace(" ", "-", ucwords(str_replace("-", " ", $k)))] =& $this->__mime->headers[$k];
            }
        }

        if (   !empty($this->__mime->parts)
            && is_array($this->__mime->parts))
        {
            // Start with empty body and append all text parts to it
            $this->body = '';
            reset ($this->__mime->parts);
            while (list ($k, $part) = each ($this->__mime->parts))
            {
                $this->_part_decode($this->__mime->parts[$k]);
            }
        }
        else
        { //No parts, just body
            switch (strtolower($this->__mime->ctype_secondary))
            {
                default:
                case "plain":
                   $this->body =& $this->__mime->body;
                break;
                case "html":
                   $this->html_body =& $this->__mime->body;
                   $this->body = $this->html2text($this->__mime->body);
                break;
            }
            if (   isset($this->__mime->ctype_parameters['charset'])
                && !$this->__orig_encoding)
            {
                $this->__orig_encoding = $this->__mime->ctype_parameters['charset'];
            }
        }

        // Charset conversions
        debug_add('calling $this->_charset_convert($this->body, $this->__orig_encoding);');
        $this->body  = $this->_charset_convert($this->body, $this->__orig_encoding);
        debug_add('calling $this->_charset_convert($this->html_body, $this->__orig_encoding);');
        $this->html_body  = $this->_charset_convert($this->html_body, $this->__orig_encoding);
        foreach ($this->headers as $header => $value)
        {
            debug_add("calling _charset_convert for header '{$header}'");
            $this->headers[$header] = $this->_charset_convert($value, $this->__orig_encoding);
        }
        foreach ($this->attachments as $key => $data)
        {
            debug_add("calling _charset_convert for attachment '{$data['name']}'");
            $this->attachments[$key]['name'] = $this->_charset_convert($data['name'], $this->__orig_encoding);
        }

        //Strip whitespace around bodies
        $this->body = ltrim(rtrim($this->body));
        $this->html_body = ltrim(rtrim($this->html_body));

        //TODO Figure if decode was successful or not and return true/false in stead
        return $this->__mime;
    }

    /**
     * Check if we have mime boundary, in that case we need to make sure it does not exhibit certain
     * corner cases which choke mail_mimedecode
     */
    private function _check_boundary()
    {
        if (preg_match("/Content-Type: multipart\/\w+;\n?\s+boundary=([\"']?)(.*?)(\\1)\n/", $this->_message, $boundary_matches))
        {
            $boundary = $boundary_matches[2];
            if (   strpos($boundary, '"')
                || strpos($boundary, "'"))
            {
                // Any quote inside the boundary value will choke mail_mimedecode
                debug_add('"corrupt" (as in will choke mail_mimedecode) boundary detected, trying to fix', MIDCOM_LOG_WARN);
                // Se we replace them with dashes
                $new_boundary = str_replace(array('"', "'"), array('-', '-'), $boundary);
                debug_add("new_boundary=\"{$new_boundary}\"");
                // Check if our new boundary exists inside the body (unlikely but I would *hate* to debug such issue)
                while (strpos($this->_message, $new_boundary))
                {
                    debug_add('Our fixed new_boundary already exists inside the body, making two random changes to it', MIDCOM_LOG_WARN);
                    //Replace two characters randomly in the boundary
                    $a = chr(rand(97, 122));
                    $b = chr(rand(97, 122));
                    $new_boundary  = substr_replace($new_boundary, $a, rand(0, strlen($new_boundary)), 1);
                    $new_boundary  = substr_replace($new_boundary, $b, rand(0, strlen($new_boundary)), 1);
                    debug_add("new_boundary=\"{$new_boundary}\"");
                }
                // Finally we have a new workable boundary, replace all instances of the old one with the new
                $this->_message = str_replace($boundary, $new_boundary, $this->_message);
            }
        }
    }

    /**
     * Decodes a Mail_mime part (recursive)
     */
    private function _part_decode(&$part)
    {
        //Check for subparts and process them if they exist
        if (   isset($part->parts)
            && is_array($part->parts)
            && count($part->parts) > 0)
        {
            reset ($part->parts);
            while (list ($k, $subPart) = each ($part->parts))
            {
                //We might recurse quite deep so pop here.
                $this->_part_decode($part->parts[$k]);
            }
            return;
        }

        // PONDER: How to handle multiple text bodies better (like in bounce messages)

        //Check attachment vs body
        if (   !isset($part->disposition)
            || (   $part->disposition == 'inline'
                && (   isset($part->ctype_primary)
                    && strtolower($part->ctype_primary) == 'text'
                    )
                )
            )
        {
            //part is (likely) body
            if (   isset($part->ctype_parameters['charset'])
                && !$this->__orig_encoding)
            {
                $this->__orig_encoding = $part->ctype_parameters['charset'];
            }
            switch (strtolower($part->ctype_secondary))
            {
                default:
                case "plain": //Always use plaintext body if found
                    // Append *only* if we already have a text body found, otherwise override
                    if (!$this->__textBodyFound)
                    {
                        $this->body = (string)$part->body;
                    }
                    else
                    {
                        $this->body .= (string)$part->body;
                    }
                    $this->__textBodyFound = true;
                    break;
                case "html":
                    if (!$this->__textBodyFound)
                    {
                        //Try to translate HTML body only if plaintext alternative is not available
                        $this->body = $this->html2text($part->body);
                    }
                    // Append *only* if we already have a HTML body found, otherwise override
                    if (!$this->__htmlBodyFound)
                    {
                        $this->html_body = (string)$part->body;
                    }
                    else
                    {
                        $this->html_body .= (string)$part->body;
                    }
                    $this->__htmlBodyFound = true;
                    break;
            }
        }
        else
        {
            //part is (likely) attachment
            /* PONDER: Should we distinguish between attachments and embeds (NOTE: adds complexity to applications
             * using this library since they need the check both arrays and those that actually need to distinguish between
             * the two can also check the attachment['part'] object for details).
             */
            $dataArr = array();
            $dataArr['part'] =& $part;
            $dataArr['mimetype'] = $part->ctype_primary . "/" . $part->ctype_secondary;
            $dataArr['content'] =& $dataArr['part']->body;
            if (   isset($part->d_parameters['filename'])
                && !empty($part->d_parameters['filename']))
            {
                $dataArr['name'] = $part->d_parameters['filename'];
            }
            else if (   isset($part->ctype_parameters['name'])
                    && !empty($part->ctype_parameters['name']))
            {
                $dataArr['name'] = $part->ctype_parameters['name'];
            }
            else
            {
                $dataArr['name'] = "unnamed";
            }
            $this->attachments[] = $dataArr;
        }
    }

    /**
     * Converts given string to $this->encoding
     *
     * @param string $data to be converted
     * @param string $given_encoding encoding from header or such, used as default in case mb_detect_endoding is not available
     * @return string converted string (or original string in case we cannot convert for some reason)
     */
    private function _charset_convert($data, $given_encoding = false)
    {
        // Some headers are multi-dimensional, recurse if needed
        if (is_array($data))
        {
            debug_add('Given data is an array, iterating trough it');
            foreach ($data as $k => $v)
            {
                debug_add("Recursing key {$k}");
                $data[$k] = $this->_charset_convert($v, $given_encoding);
            }
            debug_add('Done');
            return $data;
        }

        if (empty($data))
        {
            debug_add('Data is empty, returning as is',  MIDCOM_LOG_WARN);
            return $data;
        }
        if (!function_exists('iconv'))
        {
            debug_add('Function \'iconv()\' not available, returning data as is',  MIDCOM_LOG_WARN);
            return $data;
        }
        $encoding = false;
        if (   !function_exists('mb_detect_encoding')
            && !empty($given_encoding))
        {
            $stupid_domains = $this->_config->get('incorrect_charset_domains');
            if (!is_array($stupid_domains))
            {
                return;
            }
            foreach ($stupid_domains as $domain)
            {
                if (stristr($this->from, "@{$domain}"))
                {
                    debug_add("Detected incorrect_charset_domain '{$domain}' and 'mb_detect_encoding()' not available, aborting convert", MIDCOM_LOG_WARN);
                    return $data;
                }
            }
            $encoding =& $given_encoding;
        }
        else
        {
            $encoding = mb_detect_encoding($data, $this->_config->get('mb_detect_encoding_list'));
        }
        if (empty($encoding))
        {
            debug('Given/Detected encoding is empty, cannot convert, aborting', MIDCOM_LOG_WARN);
            return $data;
        }
        $encoding_lower = strtolower($encoding);
        $this_encoding_lower = strtolower($this->encoding);
        if (   $encoding_lower == $this_encoding_lower
            || (   $encoding_lower == 'ascii'
                /* ASCII is a subset of the following encodings, and thus requires no conversion to them */
                && (   $this_encoding_lower == 'utf-8'
                    || $this_encoding_lower == 'iso-8859-1'
                    || $this_encoding_lower == 'iso-8859-15')
                )
            )
        {
            debug_add("Given/Detected encoding '{$encoding}' and desired encoding '{$this->encoding}' require no conversion between them", MIDCOM_LOG_INFO);
            return $data;
        }
        $append_target = $this->_config->get('iconv_append_target');
        debug_add("Calling iconv('{$encoding_lower}', '{$this_encoding_lower}{$append_target}', \$data)");
        $stat = @iconv($encoding_lower, $this_encoding_lower . $append_target, $data);
        if (empty($stat))
        {
            debug_add("Failed to convert from '{$encoding}' to '{$this->encoding}'", MIDCOM_LOG_WARN);
            return $data;
        }
        debug_add("Converted from '{$encoding}' to '{$this->encoding}'", MIDCOM_LOG_INFO);
        return $stat;
    }

    /**
     * Get errormessage from mail class
     *
     * Handles also the PEAR errors from libraries used.
     */
    function get_error_message()
    {
        if (   is_object($this->__mime)
            && is_a($this->__mime, 'pear_error'))
        {
            return $this->__mime->getMessage();
        }
        return false;
    }
}
?>
