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
 * Gracefully degrades in functionality if certain PEAR libraries are
 * not available.
 *
 * <b>Sending Mails</b>
 *
 * Currently, the engine will send the emails through an autodetected backend, which
 * can be either Mail_smtp, Mail_sendmail or PHP's mail() function (in that order).
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
 * @package org.openpsa.mail
 */
class org_openpsa_mail extends midcom_baseclasses_components_purecode
{
    /**
     * Text body
     *
     * @var string
     */
    public $body;

    /**
     * key is header name, value is header data
     *
     * @var array
     */
    public $headers = array
    (
        'Subject' => null,
        'From' => null,
        'To' => null,
        'Cc' => null,
        'Bcc' => null,
    );

    /**
     * HTML body (of MIME/multipart message)
     *
     * @var string
     */
    public $html_body;

    /**
     * The parameters to use for the Mail template.
     *
     * @var array
     */
    public $parameters = array();

    /**
     * Primary keys are int, secondary keys for decoded array are:
     *
     * 'name'     (filename)
     * 'content'  (file contents)
     * 'mimetype' Array for encoding may instead of 'content' have 'file'
     *            which is path to the file to be attached
     *
     * @var array
     */
    public $attachments = array();

    /**
     * Character encoding in which the texts etc are
     *
     * @var string
     */
    public $encoding;

    /**
     * Allow to send only HTML body
     *
     * @var boolean
     */
    public $allow_only_html = false;

    /**
     * Like attachments but used for inline images.
     *
     * This is dynamically filled from html_body if embed_images is called
     *
     * @var array
     */
    private $_embeds = array();

    /**
     * The backend object
     *
     * @var org_openpsa_mail_backend
     */
    private $_backend = false;

    public function __construct($backend = 'try_default', $backend_params = array())
    {
        $this->_component = 'org.openpsa.mail';
        parent::__construct();

        $this->encoding = $this->_i18n->get_current_charset();

        $this->_backend = org_openpsa_mail_backend::get($backend, $backend_params);

        $this->headers['X-Originating-IP'] = '[' . $_SERVER['REMOTE_ADDR'] . ']';
        $this->headers['X-Mailer'] = "PHP/" . phpversion() . ' /OpenPSA/' . midcom::get('componentloader')->get_component_version($this->_component) . '/' . get_class($this->_backend);
    }

    /**
     * Make it possible to get header values via $mail->to and the like
     */
    public function __get($name)
    {
        $name = ucfirst($name);
        if (array_key_exists($name, $this->headers))
        {
            return $this->headers[$name];
        }

        return parent::__get($name);
    }

    /**
     * Make it possible to set header values via $mail->to and the like
     */
    public function __set($name, $value)
    {
        $name = ucfirst($name);
        if (array_key_exists($name, $this->headers))
        {
            $this->headers[$name] = $value;
        }
    }

    /**
     * Returns true/false depending on whether we can send attachments
     */
    function can_attach()
    {
        if (class_exists('Mail_mime'))
        {
            debug_add('Mail_mime exists: returning true');
            return true;
        }
        return false;
    }

    /**
     * Tries to convert HTML to plaintext
     */
    function html2text($html)
    {
        //Convert various newlines to unix ones
        $text = preg_replace('/\x0a\x0d|\x0d\x0a|\x0d/', "\n", $html);
        //convert <br/> tags to newlines
        $text = preg_replace("/<br\s*\\/?>/i", "\n", $text);
        //strip all STYLE and SCRIPT tags, including their content
        $text = preg_replace('/(<style[^>]*>.*?<\\/style>)/si', '', $text);
        $text = preg_replace('/(<script[^>]*>.*?<\\/script>)/si', '', $text);
        //strip comments
        $text = preg_replace('/<!--.*?-->/s', '', $text);
        //strip all remaining tags, just the tags
        $text = preg_replace('/(<[^>]*>)/', '', $text);

        //Decode entities
        $text = html_entity_decode($text, ENT_QUOTES);

        //Trim whitespace from end of lines
        $text = preg_replace("/[ \t\f]+$/m", '', $text);
        //Trim whitespace from beginning of lines
        $text = preg_replace("/^[ \t\f]+/m", '', $text);
        //Convert multiple concurrent spaces to one
        $text = preg_replace("/[ \t\f]+/", ' ', $text);
        //Strip extra linebreaks
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        //Wrap to RFC width
        $text = wordwrap($text, 72, "\n");

        return trim($text);
    }

    /**
     * Prepares message for sending
     *
     * Calls MIME etc encodings as necessary.
     */
    private function _prepare_message()
    {
        if (!empty($this->parameters))
        {
            $template_helper = new org_openpsa_mail_template($this->parameters);
            $this->headers['Subject'] = $template_helper->parse($this->headers['Subject']);
            $this->body = $template_helper->parse($this->body);
            $this->html_body = $template_helper->parse($this->html_body);
        }
        //Translate newlines
        $this->body = preg_replace("/\n\r|\r\n|\r/", "\n", $this->body);
        $this->html_body = preg_replace("/\n\r|\r\n|\r/", "\n", $this->html_body);

        //Try to translate HTML-only body to plaintext as well
        if (   strlen($this->body) == 0
            && strlen($this->html_body) > 0
            && !$this->allow_only_html)
        {
            $this->body = $this->html2text($this->html_body);
        }

        $message = new org_openpsa_mail_message($this->to, $this->headers, $this->encoding);

        //Check whether it's necessary to initialize MIME
        if (    !empty($this->html_body)
             || !empty($this->attachments)
             || !empty($this->_embeds))
        {
            $message->set_mime_body($this->body, $this->html_body, $this->attachments, $this->_embeds);
        }
        else
        {
            $message->set_body($this->body);
        }

        return $message;
    }

    /**
     * Sends the email
     */
    function send()
    {
        if (!is_object($this->_backend))
        {
            debug_add('no backend object available, aborting', MIDCOM_LOG_WARN);
            return false;
        }

        //Prepare mail for sending
        $message = $this->_prepare_message();

        $ret = $this->_backend->send($message);
        if ($ret !== true)
        {
            debug_add('Mail sending failed: ' . $this->_backend->get_error_message(), MIDCOM_LOG_ERROR);
        }
        return $ret;
    }

    /**
     * Get errormessage from mail class
     *
     * Handles also the PEAR errors from libraries used.
     */
    function get_error_message()
    {
        if (is_object($this->_backend))
        {
            return $this->_backend->get_error_message();
        }
        return 'Unknown error';
    }

    /**
     * Find embeds from source HTML, intentionally does NOT use $this->html_body
	 *
     *  @param midcom_core_dbaobject $object Optional DBA object from which attachments can be read
     */
    public function embed_images($obj = false)
    {
        //Anything with SRC = "" something in it (images etc)
        $regExp_src = "/(src|background)=([\"'�])(((https?|ftp):\/\/)?(.*?))\\2/i";
        preg_match_all($regExp_src, $this->html_body, $matches_src);
        debug_print_r("matches_src:", $matches_src);
        $tmpArr = array();
        $tmpArr['whole']    = $matches_src[0];
        $tmpArr['uri']      = $matches_src[3];
        $tmpArr['proto']    = $matches_src[4];
        $tmpArr['location'] = $matches_src[6];
        $tmpArr['type']     = $matches_src[1];

        list ($this->html_body, $this->_embeds) = $this->_html_get_embeds_loop($obj, $this->html_body, $tmpArr, $this->_embeds, 'special:fromarray');
    }

    private function _html_get_embeds_loop(&$obj, $html, $search, $embeds, $type)
    {
        $type_backup = $type;

        //Cache for embeds data
        static $embeds_data_cache = array();

        reset($search);
        while (list ($k, $dummy) = each ($search['whole']))
        {
            if ($type_backup == 'special:fromarray')
            {
                $type = $search['type'][$k];
            }
            debug_add("k: {$k}, type: {$type}, type_backup: {$type_backup}");

            $regExp_file = "/(.*\/|^)(.+?)$/";
            preg_match($regExp_file, $search['location'][$k], $match_file);
            debug_print_r("match_file:", $match_file);
            $search['filename'][$k] = $match_file[2];

            if (isset($embeds_data_cache[$search['location'][$k]]))
            {
                $mode = 'cached';
            }
            else if ($search['proto'][$k])
            { //URI is fully qualified
               $mode = 'fullUri';
               $uri = $search['uri'][$k];
            }
            else if (preg_match('/^\//', $search['location'][$k]))
            { //URI is relative
               $mode = 'relUri';
            }
            else if ($search['uri'][$k] === $search['filename'][$k])
            { //URI is just the filename
               $mode = 'objFile';
            }
            else
            { //We cannot decide what to do
               $mode = false;
            }

            debug_add('mode: ' . $mode);
            switch ($mode)
            {
                case 'cached':
                    //Avoid multiple copies of same file in embeds
                    if (!$this->_exists_in_embeds($embeds_data_cache[$search['location'][$k]], $embeds))
                    {
                        $embeds[] = $embeds_data_cache[$search['location'][$k]];
                    }
                    switch (strtolower($type))
                    {
                        case 'url':
                            $html = str_replace($search['whole'][$k], 'url("' . $search['filename'][$k] . '")', $html);
                            break;
                        default:
                            $html = str_replace($search['whole'][$k], $type . '="' . $search['filename'][$k] . '"', $html);
                            break;
                    }
                    break;
                case 'relUri':
                    switch ($_SERVER['SERVER_PORT'])
                    {
                        case 443:
                            $uri = 'https://' . $_SERVER['SERVER_NAME'] . $search['location'][$k];
                            break;
                        case 80:
                            $uri = 'http://' . $_SERVER['SERVER_NAME'] . $search['location'][$k];
                            break;
                        default:
                            $uri = 'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $search['location'][$k];
                            break;
                   }
                    // NOTE: Fall-trough intentional
                case 'fullUri':
                    debug_add('Trying to fetch file: ' . $uri);
                    $cont = @file_get_contents($uri); //Suppress errors, the url might be invalid but if so then we just silently drop it
                    if (  $cont
                          && $cont != 'FAILED REDIRECT TO ERROR find does not point to valid object MGD_ERR_OK') //Attachment server error
                    {
                        debug_add('Success!');
                        $tmpArr2 = array();
                        $tmpArr2['name'] = $search['filename'][$k];
                        $tmpArr2['content'] = $cont;
                        if ($mimetype = $this->_get_mimetype($tmpArr2['content'], $tmpArr2['name']))
                        {
                            $tmpArr2['mimetype'] = $mimetype;
                        }
                        $embeds_data_cache[$search['location'][$k]] = $tmpArr2;
                        $embeds[] = $tmpArr2;
                        switch (strtolower($type))
                        {
                            case 'url':
                                $html = str_replace($search['whole'][$k], 'url("' . $search['filename'][$k] . '")', $html);
                                break;
                            default:
                                $html = str_replace($search['whole'][$k], $type . '="' . $search['filename'][$k] . '"', $html);
                                break;
                        }
                        unset($tmpArr2, $cont);
                    }
                    else
                    {
                        debug_add('FAILURE');
                    }
                    break;
                case 'objFile':
                    if (is_object($obj))
                    {
                        $attObj = $obj->get_attachment($search['filename'][$k]);
                        if ($attObj)
                        {
                            $fp = $attObj->open('r');
                            if ($fp)
                            {
                                $tmpArr2 = array();
                                $tmpArr2['mimetype'] = $attObj->mimetype;
                                $tmpArr2['name'] = $search['filename'][$k];
                                while (!feof($fp))
                                {
                                    $tmpArr2['content'] .= fread($fp, 4096);
                                }
                                fclose($fp);
                                $embeds_data_cache[$search['location'][$k]] = $tmpArr2;
                                $embeds[] = $tmpArr2;
                                unset ($tmpArr2);
                            }
                            unset($attObj);
                        }
                    }
                    break;
            }
        }
        return array($html, $embeds);
    }

    /**
     * Determine correct mimetype for file we have only content
     * (and perhaps filename) for.
     */
    private function _get_mimetype($content, $name = 'unknown')
    {
        $filename = tempnam($GLOBALS['midcom_config']['midcom_tempdir'], 'org_openpsa_mail_') . "_{$name}";
        $fp = fopen($filename, 'w');
        if (!$fp)
        {
            //Could not open file for writing
            unlink($filename);
            return false;
        }
        fwrite($fp, $content);
        fclose($fp);
        $mimetype = midcom_helper_misc::get_mimetype($filename);
        unlink($filename);

        return $mimetype;
    }

    /**
     * Whether given file definition is already in embeds
     */
    private function _exists_in_embeds($input, $embeds)
    {
        reset($embeds);
        foreach ($embeds as $file_arr)
        {
            //PONDER: Check other values as well ?
            if ($input['name'] === $file_arr['name'])
            {
                return true;
            }
        }
        return false;
    }
}
?>
