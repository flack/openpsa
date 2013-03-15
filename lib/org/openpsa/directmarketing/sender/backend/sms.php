<?php
/**
 * @package org.openpsa.directmarketing
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Campaign message sender backend for SMS
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_sender_backend_sms implements org_openpsa_directmarketing_sender_backend
{
    /**
     * @var org_openpsa_smslib
     */
    private $_smsbroker;

    public function __construct(array $config, org_openpsa_directmarketing_campaign_message_dba $message)
    {
        $defaults = array
        (
            'sms_lib_api' => 'tambur',
            'sms_lib_location' => '',
            'sms_lib_client_id' => '',
            'sms_lib_user' => '',
            'sms_lib_password' => '',
        );
        $config = array_merge($defaults, $config);
        //Initializing SMS broker

        $this->_smsbroker = org_openpsa_smslib::factory($config['sms_lib_api']);
        if (!is_object($this->_smsbroker))
        {
            throw new midcom_error('Failed to load sms broker');
        }
        $this->_smsbroker->location = $config['sms_lib_location'];
        $this->_smsbroker->client_id = $config['sms_lib_client_id'];
        $this->_smsbroker->user = $config['sms_lib_user'];
        $this->_smsbroker->password = $config['sms_lib_password'];
    }

    /**
     * Adds necessary constraints to member QB to find valid entries
     *
     * @param midcom_core_querybuilder &$qb The QB instance to work on
     */
    public function add_member_constraints(&$qb)
    {
        $qb->add_constraint('person.handphone', '<>', '');
    }

    /**
     * Backend type, for example 'email' or 'sms'
     *
     * @return string
     */
    public function get_type()
    {
        return 'sms';
    }

    /**
     * Validate results before send
     *
     * @param array $results Array of member objects
     * @param boolean Indicating success
     */
    public function check_results(array &$results)
    {
        if (!method_exists('get_balance', $this->_smsbroker))
        {
            debug_add('Broker does not have mechanism for checking balance, supposing infinite', MIDCOM_LOG_INFO);
            return true;
        }
        debug_add('Checking SMS broker balance');
        $balance = $this->_smsbroker->get_balance();
        debug_add("Got balance '{$balance}'");
        if ($balance === false)
        {
            debug_add('Error while checking SMS broker balance, returning false to be safe', MIDCOM_LOG_ERROR);
            return false;
        }
        $results_count = count($results);
        //Non-numeric balance is supposed to be infinite
        if (   is_numeric($balance)
            && $balance < $results_count)
        {
            debug_add("Balance ({$balance}) is less than number of recipients ({$results_count})", MIDCOM_LOG_INFO);
            return false;
        }
        return true;
    }

    public function send(org_openpsa_contacts_person_dba $person, org_openpsa_directmarketing_campaign_member_dba $member, $token, $subject, $content, $from)
    {
        $person->handphone = $this->_normalize_phone($person->handphone);

        //TODO: Add sender support
        $status = $this->_smsbroker->send_sms($person->handphone, $content_p, $from);

        if ($status)
        {
            debug_add('SMS sent to: ' . $person->handphone);
            return $status;
        }
        else
        {
            throw new midcom_error(sprintf('FAILED to send SMS to: %s, reason: %s', $person->handphone, $this->_smsbroker->errstr));
        }
    }

    /**
     * Function tries to normalize the phone number to a single string of numbers
     */
    private function _normalize_phone($phone)
    {
        //Quite simplistic approach but works correctly on +358-(0)40-5401446
        return preg_replace("/(\([0-9]+\))|([^0-9+]+?)/", '', $phone);
    }
}
?>