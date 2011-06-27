<?php
/**
 * @package org.openpsa.mail
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Send backend for org_openpsa_mail, using PEAR Mail_smtp
 *
 * @package org.openpsa.mail
 */
class org_openpsa_mail_backend_mail_smtp extends org_openpsa_mail_backend
{
    private $_mail = null;

    public function __construct(array $params)
    {
        include_once('Mail.php');
        include_once('Mail/smtp.php');
        if (   !class_exists('Mail')
            || !class_exists('Mail_smtp'))
        {
            throw new midcom_error('Classes for PEAR package "Mail" could not be found');
        }
        $this->_mail = Mail::factory('smtp', $params);
    }

    public function mail($recipients, array $headers, $body)
    {
        return $this->_mail->send($recipients, $headers, $body);
    }
}
?>