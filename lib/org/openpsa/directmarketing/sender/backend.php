<?php
/**
 * @package org.openpsa.directmarketing
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Campaign message sender backend interface
 *
 * @package org.openpsa.directmarketing
 */
interface org_openpsa_directmarketing_sender_backend
{
    /**
     * Constructor
     *
     * @param array $config Backend configuration
     * @param org_openpsa_directmarketing_campaign_message_dba $message The message we're working on
     */
    public function __construct(array $config, org_openpsa_directmarketing_campaign_message_dba $message);

    /**
     * Adds necessary constraints to member QB to find valid entries
     *
     * @param midcom_core_querybuilder $qb The QB instance to work on
     */
    public function add_member_constraints($qb);

    /**
     * Validate results before send
     *
     * @param array $results Array of member objects
     * @return boolean Indicating success
     */
    public function check_results(array &$results);

    /**
     * Backend type, for example 'email' or 'sms'
     *
     * @return string
     */
    public function get_type();

    /**
     * This does the actual sending
     *
     * @param org_openpsa_contacts_person_dba $person The recipient
     * @param org_openpsa_directmarketing_campaign_member_dba $member The member object
     * @param string $token Message token
     * @param string $subject Message subject
     * @param string $content Message content
     * @param string $from Message sender
     */
    public function send(org_openpsa_contacts_person_dba $person, org_openpsa_directmarketing_campaign_member_dba $member, string $token, string $subject, string $content, $from);
}
