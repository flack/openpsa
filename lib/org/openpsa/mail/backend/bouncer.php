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
 * @package org.openpsa.mail
 */
class org_openpsa_mail_backend_bouncer
{
    var $error = false;
    var $_try_backends = array('mail_smtp', 'mail_sendmail'); //Backends that properly set the ENVELOPE address from "Return-Path" header
    var $_backend = null;

    function __construct()
    {
        foreach ($this->_try_backends as $backend)
        {
            debug_add("Trying backend {$backend}");
            if (   $this->_load_backend($backend)
                && $this->_backend->is_available())
            {
                debug_add('OK');
                break;
            }
            debug_add("backend {$backend} is not available");
        }
        return true;
    }

    function send(&$mailclass, &$params)
    {
        if (   !$this->is_available()
            || !is_object($this->_backend)
            || !method_exists($this->_backend, 'send'))
        {
            debug_add('backend is unavailable');
            $this->error = 'Backend is unavailable';
            return false;
        }
        $mailclass->headers['X-org.openpsa.mail-bouncer-backend-class'] = get_class($this->_backend);

        return $this->_backend->send($mailclass, $params);
    }

    function get_error_message()
    {
        if (   is_object($this->_backend)
            && method_exists($this->_backend, 'get_error_message'))
        {
            return $this->_backend->get_error_message();
        }
        if (!$this->error)
        {
            return false;
        }
        if (!empty($this->error))
        {
            return $this->error;
        }
        return 'Unknown error';
    }

    function is_available()
    {
        if (   !is_object($this->_backend)
            || !method_exists($this->_backend, 'is_available'))
        {
            return false;
        }
        return $this->_backend->is_available();
    }

    function _load_backend($backend)
    {
        $classname = "org_openpsa_mail_backend_{$backend}";
        if (class_exists($classname))
        {
            $this->_backend = new $classname();
            debug_print_r("backend is now:", $this->_backend);
            return true;
        }
        debug_add("backend class {$classname} is not available", MIDCOM_LOG_WARN);
        return false;
    }
}

?>