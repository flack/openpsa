<?php
/**
 * @package org.openpsa.mail
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Send backend for org_openpsa_mail, using PEAR Mail_sendmail
 * @package org.openpsa.mail
 */
class org_openpsa_mail_backend_mail_sendmail
{
    var $error = false;
    var $_mail = null;

    function __construct()
    {
        debug_add('constructor called');
        if (!class_exists('Mail'))
        {
            debug_add('class "Mail" not found trying to include Mail.php');
            @include_once('Mail.php');
        }
        if (   class_exists('Mail')
            && !class_exists('Mail_sendmail'))
        {
            debug_add('class "Mail_sendmail" not found trying to include Mail/smtp.php');
            @include_once('Mail/sendmail.php');
        }
        return true;
    }

    function send(&$mailclass, &$params)
    {
        if (!$this->is_available())
        {
            debug_add('backend is unavailable');
            $this->error = 'Backend is unavailable';
            return false;
        }
        if (!is_array($params))
        {
            $params = array();
        }
        if ($mailclass->_config->get('sendmail_path'))
        {
            $params['sendmail_path'] = $mailclass->_config->get('sendmail_path');
        }
        if ($mailclass->_config->get('sendmail_args'))
        {
            $params['sendmail_args'] = $mailclass->_config->get('sendmail_args');
        }

        $this->_mail = Mail::factory('sendmail', $params);
        $mail =& $this->_mail;
        $merged = $mailclass->merge_address_headers();
        debug_print_r("address_headers_merged:\n===\n{$merged}\n===\nheaders:", $mailclass->headers);
        $mailRet = $mail->send($merged, $mailclass->headers, $mailclass->body);
        //This gives *huge* log in case of error since the full org_openpsa_mail object is included in the PEAR error as well

        if ($mailRet === true)
        {
            $ret = true;
            $this->error = false;
        }
        else
        {
            $ret = false;
            $this->error = $mailRet;
        }

        return $ret;
    }

    function get_error_message()
    {
        if ($this->error === false)
        {
            return false;
        }
        $errObj =& $this->error;
        if (is_object($errObj))
        {
            return $errObj->getMessage();
        }
        if (!empty($this->error))
        {
            return $this->error;
        }
        return 'Unknown error';
    }

    function is_available()
    {
        return class_exists('Mail_sendmail');
    }
}
?>