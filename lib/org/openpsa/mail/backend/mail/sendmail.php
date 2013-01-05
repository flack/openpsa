<?php
/**
 * @package org.openpsa.mail
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Send backend for org_openpsa_mail, using PEAR Mail_sendmail
 *
 * @package org.openpsa.mail
 */
class org_openpsa_mail_backend_mail_sendmail extends org_openpsa_mail_backend
{
    public function __construct(array $params)
    {
        $this->_mail = Mail::factory('sendmail', $params);
    }

    public function mail($recipients, array $headers, $body)
    {
        return $this->_mail->send($recipients, $headers, $body);
    }
}
?>
