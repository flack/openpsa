<?php
/**
 * @package org.openpsa.notifications
 * @author Henri Bergius, http://bergie.iki.fi
 * @copyright Nemein Oy, http://www.nemein.com
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Class for sending notices to nabaztag
 *
 * @package org.openpsa.notifications
 */
class org_openpsa_notifications_notifier_api_nabaztag extends org_openpsa_notifications_notifier
{
    /**
     * Sends the 'abstract' version of the message as a message to the Nabaztag configured for the system
     */
    function send_nabaztag($message)
    {
        @include_once('Services/Nabaztag.php');
        if (!class_exists('Services_Nabaztag'))
        {
            debug_add("Services_Nabaztag library not installed", MIDCOM_LOG_DEBUG);
            return false;
        }
        
        if (   !$this->_config->get('nabaztag_serial_number')
            || !$this->_config->get('nabaztag_api_token'))
        {
            debug_add("Nabaztag serial number or API token not set", MIDCOM_LOG_DEBUG);
            return false;
        }
        
        $nabaztag = new Services_Nabaztag($this->_config->get('nabaztag_serial_number'), $this->_config->get('nabaztag_api_token'));
        
        $stat = false;
        try
        {
            $stat = $nabaztag->say($message['abstract']);
        }
        catch (Services_Nabaztag_Exception $e)
        {
            debug_add($e, MIDCOM_LOG_DEBUG);
            return false;
        }
        
        if ($stat)
        {
            $_MIDCOM->uimessages->add($_MIDCOM->i18n->get_string('org.openpsa.notifications', 'org.openpsa.notifications'), sprintf($_MIDCOM->i18n->get_string('notification sent to %s', 'org.openpsa.notifications'), "Nabaztag"));
        }
        
        return $stat;
    }

}
?>