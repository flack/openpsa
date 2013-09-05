<?php
/**
 * @package org.openpsa.mail
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Send backend for org_openpsa_mail, using PHPs mail() function
 *
 * @package org.openpsa.mail
 */
class org_openpsa_mail_backend_mail extends org_openpsa_mail_backend
{
    public function __construct($params)
    {
        $this->_mail = Swift_MailTransport::newInstance($params);
    }

    public function mail($recipients, array $headers, $body)
    {
        $real_headers = array();
        $subject = '';
        reset($headers);
        foreach ($headers as $key => $value)
        {
            if (strtolower($key) == 'to')
            {
                continue;
            }
            else if (strtolower($key) == 'subject')
            {
                $subject = $value;
                continue;
            }
            $real_headers[$key] = $value;
        }

        // create message
        $message = Swift_Message::newInstance($subject)
        ->setTo($recipients)
        ->setBody($body);
        
        // set headers
        $headers = $message->getHeaders();
        foreach ($real_headers as $name => $value)
        {
            $headers->addTextHeader($name, $value);
        }
        
        return $this->_mail->send($message);
    }
}
?>