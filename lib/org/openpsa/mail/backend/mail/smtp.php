<?php
/**
 * @package org.openpsa.mail
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

/**
 * Send backend for org_openpsa_mail
 *
 * @package org.openpsa.mail
 */
class org_openpsa_mail_backend_mail_smtp extends org_openpsa_mail_backend
{
    public function __construct(array $params)
    {
        $transport = new EsmtpTransport($params['host'], (int) $params['port']);

        if (!empty($params['username'])) {
            $transport->setUsername($params['username']);
        }
        if (!empty($params['password'])) {
            $transport->setPassword($params['password']);
        }

        $this->prepare_mailer($transport);
    }

    public function mail(org_openpsa_mail_message $message)
    {
        return $this->mailer->send($message->get_message());
    }
}
