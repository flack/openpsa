<?php
/**
 * @package org.openpsa.directmarketing
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\schemadb;

/**
 * Importer for subscribers
 *
 * @package org.openpsa.directmarketing
 */
abstract class org_openpsa_directmarketing_importer extends midcom_baseclasses_components_purecode
{
    /**
     * Datamanagers used for saving various objects like persons and organizations
     *
     * @var datamanager[]
     */
    private $_datamanagers = [];

    /**
     * The schema databases used for importing to various objects like persons and organizations
     *
     * @var schemadb[]
     */
    protected $_schemadbs = [];

    /**
     * Object registry
     *
     * @var array
     */
    private $_new_objects = [];

    /**
     * Status table
     *
     * @var array
     */
    private $_import_status = [];

    /**
     * Importer configuration, if any
     *
     * @var array
     */
    protected $_settings = [];

    public function __construct(array $schemadbs, array $settings = [])
    {
        parent::__construct();
        $this->_settings = $settings;
        $this->_schemadbs = $schemadbs;
        $this->_datamanagers['campaign_member'] = new datamanager($this->_schemadbs['campaign_member']);
        $this->_datamanagers['person'] = new datamanager($this->_schemadbs['person']);
        $this->_datamanagers['organization_member'] = new datamanager($this->_schemadbs['organization_member']);
        $this->_datamanagers['organization'] = new datamanager($this->_schemadbs['organization']);
    }

    /**
     * Converts input into the importer array format
     *
     * @param mixed $input
     * @return array
     */
    abstract public function parse($input);

    private function _datamanager_process(string $type, array $subscriber, midcom_core_dbaobject $object)
    {
        if (empty($subscriber[$type])) {
            // No fields for this type, skip DM phase
            return;
        }

        $this->_datamanagers[$type]
            ->set_storage($object)
            ->get_form()
            ->submit($subscriber[$type]);

        $this->_datamanagers[$type]->get_storage()->save();
    }

    private function _clean_new_objects()
    {
        foreach ($this->_new_objects as $object) {
            $object->delete();
        }
    }

    private function _import_subscribers_person(array $subscriber) : org_openpsa_contacts_person_dba
    {
        $person = null;
        if ($this->_config->get('csv_import_check_duplicates')) {
            if (!empty($subscriber['person']['email'])) {
                // Perform a simple email test. More complicated duplicate checking is best left to the o.o.contacts duplicate checker
                $qb = org_openpsa_contacts_person_dba::new_query_builder();
                $qb->add_constraint('email', '=', $subscriber['person']['email']);
                $persons = $qb->execute_unchecked();
                if (!empty($persons)) {
                    // Match found, use it
                    $person = $persons[0];
                }
            }

            if (   !$person
                && !empty($subscriber['person']['handphone'])) {
                // Perform a simple cell phone test. More complicated duplicate checking is best left to the o.o.contacts duplicate checker
                $qb = org_openpsa_contacts_person_dba::new_query_builder();
                $qb->add_constraint('handphone', '=', $subscriber['person']['handphone']);
                $persons = $qb->execute_unchecked();
                if (!empty($persons)) {
                    // Match found, use it
                    $person = $persons[0];
                }
            }
        }

        if (!$person) {
            // We didn't have person matching the email in DB. Create a new one.
            $person = new org_openpsa_contacts_person_dba();

            // Populate at least one field for the new person
            if (!empty($subscriber['person']['email'])) {
                $person->email = $subscriber['person']['email'];
            }

            if (!$person->create()) {
                $this->_import_status['failed_create']++;
                throw new midcom_error("Failed to create person, reason " . midcom_connection::get_error_string());
            }
            $this->_new_objects['person'] = $person;
        }

        $this->_datamanager_process('person', $subscriber, $person);

        return $person;
    }

    private function _import_subscribers_campaign_member(array $subscriber, org_openpsa_contacts_person_dba $person, org_openpsa_directmarketing_campaign_dba $campaign)
    {
        // Check if person is already in campaign
        $qb = org_openpsa_directmarketing_campaign_member_dba::new_query_builder();
        $qb->add_constraint('person', '=', $person->id);
        $qb->add_constraint('campaign', '=', $campaign->id);
        $qb->add_constraint('orgOpenpsaObtype', '<>', org_openpsa_directmarketing_campaign_member_dba::TESTER);
        $members = $qb->execute_unchecked();
        if (!empty($members)) {
            // User is or has been subscriber earlier, update status
            $member = $members[0];

            if (   $member->orgOpenpsaObtype == org_openpsa_directmarketing_campaign_member_dba::UNSUBSCRIBED
                || $member->orgOpenpsaObtype == org_openpsa_directmarketing_campaign_member_dba::NORMAL) {
                $this->_import_status['already_subscribed']++;
                return;
            }
            $member->orgOpenpsaObtype = org_openpsa_directmarketing_campaign_member_dba::NORMAL;
            if (!$member->update()) {
                $this->_import_status['failed_add']++;
                throw new midcom_error('Failed to save membership: ' . midcom_connection::get_error_string());
            }
            if (array_key_exists('person', $this->_new_objects)) {
                $this->_import_status['subscribed_new']++;
            } else {
                $this->_import_status['already_subscribed']++;
            }
        } else {
            // Not a subscribed member yet, add
            $member = new org_openpsa_directmarketing_campaign_member_dba();
            $member->person = $person->id;
            $member->campaign = $campaign->id;
            $member->orgOpenpsaObtype = org_openpsa_directmarketing_campaign_member_dba::NORMAL;
            if (!$member->create()) {
                $this->_import_status['failed_add']++;
                throw new midcom_error('Failed to create membership: ' . midcom_connection::get_error_string());
            }
            $this->_new_objects['campaign_member'] = $member;
            $this->_import_status['subscribed_new']++;
        }

        $this->_datamanager_process('campaign_member', $subscriber, $member);
    }

    private function _import_subscribers_organization(array $subscriber) : org_openpsa_contacts_group_dba
    {
        $organization = null;
        if (!empty($subscriber['organization']['official'])) {
            // Perform a simple check for existing organization. More complicated duplicate checking is best left to the o.o.contacts duplicate checker

            $qb = org_openpsa_contacts_group_dba::new_query_builder();
            $schema = $this->_schemadbs['organization']->get('default');
            if (   $schema->has_field('company_id')
                && !empty($subscriber['organization']['company_id'])) {
                // Imported data has a company id, we use that instead of name
                $qb->add_constraint($schema->get_field('company_id')['storage']['location'], '=', $subscriber['organization']['company_id']);
            } else {
                // Seek by official name
                $qb->add_constraint('official', '=', $subscriber['organization']['official']);

                if (   $schema->has_field('city')
                    && !empty($subscriber['organization']['city'])) {
                    // Imported data has a city, we use also that for matching
                    $qb->add_constraint($schema->get_field('city')['storage']['location'], '=', $subscriber['organization']['city']);
                }
            }

            if ($organizations = $qb->execute_unchecked()) {
                // Match found, use it
                $organization = array_shift($organizations);
            }
        }

        if (!$organization) {
            // We didn't have person matching the email in DB. Create a new one.
            $organization = new org_openpsa_contacts_group_dba();
            if (!$organization->create()) {
                throw new midcom_error("Failed to create organization, reason " . midcom_connection::get_error_string());
            }
        }

        $this->_datamanager_process('organization', $subscriber, $organization);

        return $organization;
    }

    private function _import_subscribers_organization_member(array $subscriber, org_openpsa_contacts_person_dba $person, org_openpsa_contacts_group_dba $organization)
    {
        // Check if person is already in organization
        $qb = midcom_db_member::new_query_builder();
        $qb->add_constraint('uid', '=', $person->id);
        $qb->add_constraint('gid', '=', $organization->id);
        $members = $qb->execute_unchecked();
        if (!empty($members)) {
            // Match found, use it
            $member = $members[0];
        } else {
            // We didn't have person matching the email in DB. Create a new one.
            $member = new midcom_db_member();
            $member->uid = $person->id;
            $member->gid = $organization->id;
            if (!$member->create()) {
                throw new midcom_error("Failed to create organization member, reason " . midcom_connection::get_error_string());
            }
        }

        $this->_datamanager_process('organization_member', $subscriber, $member);
    }

    /**
     * Takes an array of new subscribers and processes each of them using datamanager.
     *
     * @return array Import status
     */
    public function import_subscribers(array $subscribers, org_openpsa_directmarketing_campaign_dba $campaign) : array
    {
        $this->_import_status = [
            'already_subscribed' => 0,
            'subscribed_new' => 0,
            'failed_create' => 0,
            'failed_add' => 0,
        ];

        foreach ($subscribers as $subscriber) {
            // Submethods will register any objects they create to this array so we can clean them up as needed
            $this->_new_objects = [];

            try {
                $person = $this->_import_subscribers_person($subscriber);
                $this->_import_subscribers_campaign_member($subscriber, $person, $campaign);

                if (!empty($subscriber['organization'])) {
                    $organization = $this->_import_subscribers_organization($subscriber);
                    $this->_import_subscribers_organization_member($subscriber, $person, $organization);
                }
            } catch (midcom_error $e) {
                $e->log();
                // Clean up possibly created data
                $this->_clean_new_objects();

                // Skip to next
                continue;
            }

            // All done, import the next one
            debug_add("Person $person->name (#{$person->id}) all processed");
        }
        return $this->_import_status;
    }
}
