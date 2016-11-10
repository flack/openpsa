<?php
/**
 * @package org.openpsa.mail
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Send backend for org_openpsa_mail, using jzs bounce detection system
 * in fact the bounce detect can work both with mail_smtp and mail_sendmail
 * but to simplify usage we wrap both here into this "meta backend"
 *
 * @package org.openpsa.mail
 */
class org_openpsa_mail_backend_bouncer extends org_openpsa_mail_backend
{
    private $_try_backends = array('mail_smtp', 'mail_sendmail'); //Backends that properly set the ENVELOPE address from "Return-Path" header
    private $_backend = null;

    public function __construct(array $params)
    {
        foreach ($this->_try_backends as $backend) {
            try {
                $this->_backend = org_openpsa_mail_backend::get($backend, $params);
            } catch (midcom_error $e) {
                debug_add('Failed to load backend ' . $backend . ', message:' . $e->getMessage());
            }
        }
        throw new midcom_error('All backends failed to load');
    }

    public function mail(org_openpsa_mail_message $message)
    {
        $message->set_header_field('X-org.openpsa.mail-bouncer-backend-class', get_class($this->_backend));
        return $this->_backend->mail($message);
    }

    public function get_error_message()
    {
        if (is_object($this->_backend)) {
            return $this->_backend->get_error_message();
        }
        return parent::get_error_message();
    }
}
