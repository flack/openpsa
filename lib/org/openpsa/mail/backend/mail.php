<?php
/**
 * @package org.openpsa.mail
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Send backend for org_openpsa_mail
 *
 * @package org.openpsa.mail
 */
class org_openpsa_mail_backend_mail extends org_openpsa_mail_backend
{
    public function __construct(array $params)
    {
        $transport = Swift_MailTransport::newInstance($params['additional_params']);
        $this->prepare_mailer($transport, $params);
    }

    public function mail(org_openpsa_mail_message $message)
    {
        return $this->mailer->send($message->get_message());
    }
}
