<?php
/**
 * @package org.openpsa.directmarketing
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Importer for subscribers
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_importer extends midcom_baseclasses_components_purecode
{
    /**
     * Datamanagers used for saving various objects like persons and organizations
     *
     * @var array
     */
    private $_datamanagers = array();

    /**
     * The schema databases used for importing to various objects like persons and organizations
     *
     * @var array
     */
    private $_schemadbs = array();

    /**
     * Object registry
     *
     * @var array
     */
    private $_new_objects = array();

    /**
     * Status table
     *
     * @var array
     */
    private $_import_status = array();

    /**
     * @param array $schemadbs The datamanager schemadbs to work on
     */
    public function __construct(array $schemadbs)
    {
        $this->_schemadbs = $schemadbs;
        $this->_datamanagers['campaign_member'] = new midcom_helper_datamanager2_datamanager($this->_schemadbs['campaign_member']);
        $this->_datamanagers['person'] = new midcom_helper_datamanager2_datamanager($this->_schemadbs['person']);
        $this->_datamanagers['organization_member'] = new midcom_helper_datamanager2_datamanager($this->_schemadbs['organization_member']);
        $this->_datamanagers['organization'] = new midcom_helper_datamanager2_datamanager($this->_schemadbs['organization']);
    }


    /**
     * Process the datamanager
     *
     * @param String $type        Subscription type
     * @param array $subscriber
     * @param mixed $object
     * @return boolean            Indicating success
     */
    private function _datamanager_process($type, array $subscriber, $object)
    {
        if (   !array_key_exists($type, $subscriber)
                || count($subscriber[$type]) == 0)
        {
            // No fields for this type, skip DM phase
            return true;
        }

        // Load datamanager2 for the object
        if (!$this->_datamanagers[$type]->autoset_storage($object))
        {
            return false;
        }

        // Set all given values into DM2
        foreach ($subscriber[$type] as $key => $value)
        {
            if (array_key_exists($key, $this->_datamanagers[$type]->types))
            {
                $this->_datamanagers[$type]->types[$key]->value = $value;
            }
        }

        // Save the object
        if (!$this->_datamanagers[$type]->save())
        {
            return false;
        }

        return true;
    }

    /**
     * Clean the new objects
     */
    private function _clean_new_objects()
    {
        foreach ($this->_new_objects as $object)
        {
            $object->delete();
        }
    }

    private function _import_subscribers_person($subscriber)
    {
        $person = null;
        if ($this->_config->get('csv_import_check_duplicates'))
        {
            if (   array_key_exists('email', $subscriber['person'])
                    && $subscriber['person']['email'])
            {
                // Perform a simple email test. More complicated duplicate checking is best left to the o.o.contacts duplicate checker
                $qb = org_openpsa_contacts_person_dba::new_query_builder();
                $qb->add_constraint('email', '=', $subscriber['person']['email']);
                $persons = $qb->execute_unchecked();
                if (count($persons) > 0)
                {
                    // Match found, use it
                    $person = $persons[0];
                }
            }

            if (   !$person
                    && array_key_exists('handphone', $subscriber['person'])
                    && $subscriber['person']['handphone'])
            {
                // Perform a simple cell phone test. More complicated duplicate checking is best left to the o.o.contacts duplicate checker
                $qb = org_openpsa_contacts_person_dba::new_query_builder();
                $qb->add_constraint('handphone', '=', $subscriber['person']['handphone']);
                $persons = $qb->execute_unchecked();
                if (count($persons) > 0)
                {
                    // Match found, use it
                    $person = $persons[0];
                }
            }
        }

        if (!$person)
        {
            // We didn't have person matching the email in DB. Create a new one.
            $person = new org_openpsa_contacts_person_dba();

            // Populate at least one field for the new person
            if (   isset($subscriber['person'])
                    && isset($subscriber['person']['email']))
            {
                $person->email = $subscriber['person']['email'];
            }

            if (!$person->create())
            {
                $this->_new_objects['person'] =& $person;
                debug_add("Failed to create person, reason " . midcom_connection::get_error_string());
                $this->_import_status['failed_create']++;
                return false;
                // This will skip to next
            }
        }

        if (!$this->_datamanager_process('person', $subscriber, $person))
        {
            return false;
        }

        return $person;
    }

    private function _import_subscribers_campaign_member($subscriber, $person, org_openpsa_directmarketing_campaign_dba $campaign)
    {
        // Check if person is already in campaign
        $member = null;
        $qb = org_openpsa_directmarketing_campaign_member_dba::new_query_builder();
        $qb->add_constraint('person', '=', $person->id);
        $qb->add_constraint('campaign', '=', $campaign->id);
        $qb->add_constraint('orgOpenpsaObtype', '<>', org_openpsa_directmarketing_campaign_member_dba::TESTER);
        $members = $qb->execute_unchecked();
        if (count($members) > 0)
        {
            // User is or has been subscriber earlier, update status
            $member = $members[0];

            // Fix http://trac.midgard-project.org/ticket/1112
            if ($member->orgOpenpsaObtype == org_openpsa_directmarketing_campaign_member_dba::UNSUBSCRIBED)
            {
                // PONDER: Which code to use ??
                //$this->_import_status['failed_add']++;
                // PONDER: What is the difference between these two?
                $this->_import_status['already_subscribed']++;
                $this->_import_status['subscribed_existing']++;
                // PONDER: Should we skip any updates, they're usually redundant but ne never knows..
                return $member;
            }
            else if ($member->orgOpenpsaObtype == org_openpsa_directmarketing_campaign_member_dba::NORMAL)
            {
                // PONDER: What is the difference between these two?
                $this->_import_status['already_subscribed']++;
                $this->_import_status['subscribed_existing']++;
            }
            else
            {
                $member->orgOpenpsaObtype = org_openpsa_directmarketing_campaign_member_dba::NORMAL;
                if ($member->update())
                {
                    if (array_key_exists('person', $this->_new_objects))
                    {
                        $this->_import_status['subscribed_new']++;
                    }
                    else
                    {
                        $this->_import_status['subscribed_existing']++;
                    }
                }
                else
                {
                    $this->_import_status['failed_add']++;
                    return false;
                }
            }
        }

        if (!$member)
        {
            // Not a subscribed member yet, add
            $member = new org_openpsa_directmarketing_campaign_member_dba();
            $member->person = $person->id;
            $member->campaign = $campaign->id;
            $member->orgOpenpsaObtype = org_openpsa_directmarketing_campaign_member_dba::NORMAL;
            if (!$member->create())
            {
                $this->_import_status['failed_add']++;
                return false;
            }
            $this->_new_objects['campaign_member'] =& $member;
            $this->_import_status['subscribed_new']++;
        }

        if (!$this->_datamanager_process('campaign_member', $subscriber, $person))
        {
            // Failed to handle campaign member via DM
            return false;
        }

        return $member;
    }

    private function _import_subscribers_organization($subscriber)
    {
        $organization = null;
        if (   array_key_exists('official', $subscriber['organization'])
                && $subscriber['organization']['official'])
        {
            // Perform a simple check for existing organization. More complicated duplicate checking is best left to the o.o.contacts duplicate checker

            $qb = org_openpsa_contacts_group_dba::new_query_builder();

            if (   array_key_exists('company_id', $this->_schemadbs['organization']['default']->fields)
                    && array_key_exists('company_id', $subscriber['organization'])
                    && $subscriber['organization']['company_id'])
            {
                // Imported data has a company id, we use that instead of name
                $qb->add_constraint($this->_schemadbs['organization']['default']->fields['company_id']['storage']['location'], '=', $subscriber['organization']['company_id']);
            }
            else
            {
                // Seek by official name
                $qb->add_constraint('official', '=', $subscriber['organization']['official']);

                if (   array_key_exists('city', $this->_schemadbs['organization']['default']->fields)
                        && array_key_exists('city', $subscriber['organization'])
                        && $subscriber['organization']['city'])
                {
                    // Imported data has a city, we use also that for matching
                    $qb->add_constraint($this->_schemadbs['organization']['default']->fields['city']['storage']['location'], '=', $subscriber['organization']['city']);
                }
            }

            $organizations = $qb->execute_unchecked();
            if (count($organizations) > 0)
            {
                // Match found, use it

                // Use first match
                $organization = array_shift($organizations);
            }
        }

        if (!$organization)
        {
            // We didn't have person matching the email in DB. Create a new one.
            $organization = new org_openpsa_contacts_group_dba();
            if (!$organization->create())
            {
                $this->_new_objects['organization'] =& $organization;
                debug_add("Failed to create organization, reason " . midcom_connection::get_error_string());
                return null;
            }
        }

        if (!$this->_datamanager_process('organization', $subscriber, $organization))
        {
            return null;
        }

        return $organization;
    }

    private function _import_subscribers_organization_member($subscriber, $person, $organization)
    {
        // Check if person is already in organization
        $member = null;
        $qb = midcom_db_member::new_query_builder();
        $qb->add_constraint('uid', '=', $person->id);
        $qb->add_constraint('gid', '=', $organization->id);
        $members = $qb->execute_unchecked();
        if (count($members) > 0)
        {
            // Match found, use it

            // Use first match
            $member = $members[0];
        }

        if (!$member)
        {
            // We didn't have person matching the email in DB. Create a new one.
            $member = new midcom_db_member();
            $member->uid = $person->id;
            $member->gid = $organization->id;
            if (!$member->create())
            {
                $this->_new_objects['organization_member'] =& $member;
                debug_add("Failed to create organization member, reason " . midcom_connection::get_error_string());
                return false;
            }
        }

        if (!$this->_datamanager_process('organization_member', $subscriber, $member))
        {
            return false;
        }

        return $member;
    }

    /**
     * Takes an array of new subscribers and processes each of them using datamanager2.
     *
     * @param array $subscribers The subscribers to import
     * @param org_openpsa_directmarketing_campaign_dba $campaign The campaign to import into
     * @return array Import status
     */
    public function import_subscribers(array $subscribers, org_openpsa_directmarketing_campaign_dba $campaign)
    {
        $this->_import_status = array
        (
            'already_subscribed' => 0,
            'subscribed_new' => 0,
            'subscribed_existing' => 0,
            'failed_create' => 0,
            'failed_add' => 0,
        );

        foreach ($subscribers as $subscriber)
        {
            // Submethods will register any objects they create to this array so we can clean them up as needed
            $this->_new_objects = array();

            // Create or update person
            $person = $this->_import_subscribers_person($subscriber);
            if (!$person)
            {
                // Clean up possible created data
                $this->_clean_new_objects();

                // Skip to next
                continue;
            }

            // Create or update membership
            $campaign_member = $this->_import_subscribers_campaign_member($subscriber, $person, $campaign);
            if (!$campaign_member)
            {
                // Clean up possible created data
                $this->_clean_new_objects();

                // Skip to next
                continue;
            }

            if (   array_key_exists('organization', $subscriber)
                    && count($subscriber['organization']) > 0)
            {
                // Create or update organization
                $organization = $this->_import_subscribers_organization($subscriber);
                if (is_null($organization))
                {
                    // Clean up possible created data
                    $this->_clean_new_objects();

                    // Skip to next
                    continue;
                }

                // Create or update organization member
                $organization_member = $this->_import_subscribers_organization_member($subscriber, $person, $organization);
                if (!$organization_member)
                {
                    // Clean up possible created data
                    $this->_clean_new_objects();

                    // Skip to next
                    continue;
                }
            }

            // All done, import the next one
            debug_add("Person $person->name (#{$person->id}) all processed");
        }
        return $this->_import_status;
    }
}