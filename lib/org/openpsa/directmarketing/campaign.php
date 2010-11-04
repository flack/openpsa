<?php
/**
 * @package org.openpsa.directmarketing
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * MidCOM DBA wrapped access to org_openpsa_campaign object, with some utility methods
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_campaign_dba extends __org_openpsa_directmarketing_campaign_dba
{
    var $testers = array(); // List of tests members (stored as campaign_members, referenced here for easier access)

    var $rules = array(); //rules for smart-campaign

    function __construct($id = null)
    {
        $stat = parent::__construct($id);
        if (   !$this->orgOpenpsaObtype
            && $stat)
        {
            $this->orgOpenpsaObtype = ORG_OPENPSA_OBTYPE_CAMPAIGN;
        }
        return $stat;
    }

    function _on_updated()
    {
        // Sync the object's ACL properties into MidCOM ACL system
        /* This craps out for some reason, and it wasn't such a hit idea afterall...
        $sync = new org_openpsa_core_acl_synchronizer();
        $sync->write_acls($this, $this->orgOpenpsaOwnerWg, $this->orgOpenpsaAccesstype);
        */
        return true;
    }

    function _on_loaded()
    {
        $this->_unserialize_rules();
        if (!is_array($this->rules))
        {
            $this->rules = array();
        }
        return true;
    }

    function _on_creating()
    {
        $this->_serialize_rules();
        return true;
    }

    function _on_updating()
    {
        $this->_serialize_rules();
        return true;
    }

    /**
     * Populates the testers array from memberships
     */
    function get_testers()
    {
        if (!$this->id)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('This campaign has no id (maybe not created yet?), aborting', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        $mc = org_openpsa_directmarketing_campaign_member_dba::new_collector('campaign', $this->id);
        $mc->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_CAMPAIGN_TESTER);
        $mc->add_value_property('person');
        $mc->execute();

        //Just to be sure
        $this->testers = array();
        foreach ($mc->list_keys() as $guid => $empty)
        {
            $this->testers[$mc->get_subkey($guid, 'person')] = true;
        }
    }

    /**
     * Unserializes rulesSerialized to rules
     */
    function _unserialize_rules()
    {
        $unserRet = @unserialize($this->rulesSerialized);
        if ($unserRet === false)
        {
            //Unserialize failed (probably newline/encoding issue), try to fix the serialized string and unserialize again
            $unserRet = @unserialize($this->_fix_serialization($this->rulesSerialized));
            if ($unserRet === false)
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add('Failed to unserialize rulesSerialized', MIDCOM_LOG_WARN);
                debug_pop();
                $this->rules = array();
                return;
            }
        }
        $this->rules = $unserRet;
    }

    /**
     * Serializes rules to rulesSerialized
     */
    function _serialize_rules()
    {
        $this->rulesSerialized = serialize($this->rules);
    }

    /**
     * Fixes newline etc encoding issues in serialized data
     *
     * @param string $data The data to fix.
     * @return string $data with serializations fixed.
     */
    function _fix_serialization($data = null)
    {
        return org_openpsa_helpers::fix_serialization($data);
    }

    /**
     * Creates/Removes members for this smart campaign based on the rules array
     * NOTE: This is highly resource intensive for large campaigns
     * @return boolean indicating success/failure
     */
    function update_smart_campaign_members()
    {
        //Disable limits
        @ini_set('memory_limit', -1);
        @ini_set('max_execution_time', 0);
        debug_push_class(__CLASS__, __FUNCTION__);
        if (!$this->id)
        {
            debug_add('This campaign has no id (maybe not created yet?), aborting', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        if ($this->orgOpenpsaObtype != ORG_OPENPSA_OBTYPE_CAMPAIGN_SMART)
        {
            debug_add("This (id #{$this->id}) is not a smart campaign, aborting", MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        $_MIDCOM->auth->request_sudo('org.openpsa.directmarketing');
        $this->parameter('org.openpsa.directmarketing_smart_campaign', 'members_update_failed', '');
        $this->parameter('org.openpsa.directmarketing_smart_campaign', 'members_update_started', time());

        $solver = new org_openpsa_directmarketing_campaign_ruleresolver();
        $rret = $solver->resolve($this->rules);
        if (!$rret)
        {
            $this->parameter('org.openpsa.directmarketing_smart_campaign', 'members_update_failed', time());
            debug_add('Failed to resolve rules', MIDCOM_LOG_ERROR);
            debug_print_r("this->rules has value:", $this->rules);
            debug_pop();
            $_MIDCOM->auth->drop_sudo();
            return false;
        }
        //returns now the result array of collector instead array of objects of query builder
        $rule_persons =  $solver->execute();
        //debug_add("solver->execute() returned with:\n===\n" . org_openpsa_helpers::sprint_r($rule_persons) . "===\n");
        if (!is_array($rule_persons))
        {
            $this->parameter('org.openpsa.directmarketing_smart_campaign', 'members_update_failed', time());
            debug_pop('Failure when executing rules based search', MIDCOM_LOG_ERROR);
            debug_pop();
            $_MIDCOM->auth->drop_sudo();
            return false;
        }

        //Create some useful maps
        $wanted_persons = array();
        $rule_persons_id_map = array();
        foreach ($rule_persons as $id => $person)
        {
            $wanted_persons[] = $id;
            $rule_persons_id_map[$id] = $person['guid'];
        }

        //Delete (normal) members that should not be here anymore
        $qb_unwanted = org_openpsa_directmarketing_campaign_member_dba::new_query_builder();
        $qb_unwanted->add_constraint('campaign', '=', $this->id);
        $qb_unwanted->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_CAMPAIGN_MEMBER);

        if (sizeof($wanted_persons) > 0)
        {
            $qb_unwanted->add_constraint('person', 'NOT IN', $wanted_persons);
        }

        $uwret = $qb_unwanted->execute();
        if (   is_array($uwret)
            && !empty($uwret))
        {
            foreach ($uwret as $member)
            {
                debug_add("Deleting unwanted member #{$member->id} (linked to person #{$member->person}) in campaign #{$this->id}");
                $delret = $member->delete();
                if (!$delret)
                {
                    debug_add("Failed to delete unwanted member #{$member->id} (linked to person #{$member->person}) in campaign #{$this->id}, reason: " . midcom_application::get_error_string(), MIDCOM_LOG_ERROR);
                }
            }
        }

        //List current non-tester members (including unsubscribed etc), and filter those out of rule_persons
        $qb_current = org_openpsa_directmarketing_campaign_member_dba::new_query_builder();
        $qb_current->add_constraint('campaign', '=', $this->id);
        $qb_current->add_constraint('orgOpenpsaObtype', '<>', ORG_OPENPSA_OBTYPE_CAMPAIGN_TESTER);
        $cret = $qb_current->execute();
        if (   is_array($cret)
            && !empty($cret))
        {
            foreach ($cret as $member)
            {
                //Filter the existing member from rule_persons (if present, for example unsubscribed members might not be)
                if (   !array_key_exists($member->person, $rule_persons_id_map)
                    || !array_key_exists($rule_persons_id_map[$member->person], $rule_persons))
                {
                    continue;
                }
                debug_add("Removing person #{$rule_persons[$rule_persons_id_map[$member->person]]->id} ({$rule_persons[$rule_persons_id_map[$member->person]]->rname}) from rule_persons list, already a member");
                unset($rule_persons[$rule_persons_id_map[$member->person]]);
            }
        }

        //Finally, create members of each person matched by rule left
        reset ($rule_persons);
        foreach ($rule_persons as $id => $person)
        {
            debug_add("Creating new member (linked to person #{$id}) to campaign #{$this->id}");
            $member = new org_openpsa_directmarketing_campaign_member_dba();
            $member->orgOpenpsaObtype = ORG_OPENPSA_OBTYPE_CAMPAIGN_MEMBER;
            $member->campaign = $this->id;
            $member->person = $id;
            $mcret = $member->create();
            if (!$mcret)
            {
                debug_add("Failed to create new member (linked to person #{$id}) in campaign #{$this->id}, reason: " . midcom_application::get_error_string(), MIDCOM_LOG_ERROR);
            }
        }

        //All done, set last updated timestamp
        $this->parameter('org.openpsa.directmarketing_smart_campaign', 'members_updated', time());

        $_MIDCOM->auth->drop_sudo();
        return true;
    }

    /**
     * Schedules a background memberships update for a smart campaign
     */
    function schedule_update_smart_campaign_members($time = false)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        if (!$time)
        {
            $time = time();
        }
        if (!$this->id)
        {
            debug_add('This campaign has no id (maybe not created yet?), aborting', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        if ($this->orgOpenpsaObtype != ORG_OPENPSA_OBTYPE_CAMPAIGN_SMART)
        {
            debug_add("This (id #{$this->id}) is not a smart campaign, aborting", MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        $_MIDCOM->auth->request_sudo('org.openpsa.directmarketing');
        $stat = midcom_services_at_interface::register($time, 'org.openpsa.directmarketing', 'background_update_campaign_members', array('campaign_guid' => $this->guid));
        if (!$stat)
        {
            debug_add('Failed to register an AT job for members update, errstr: ' . midcom_application::get_error_string(), MIDCOM_LOG_ERROR);
            $_MIDCOM->auth->drop_sudo();
            debug_pop();
            return false;
        }
        $this->parameter('org.openpsa.directmarketing_smart_campaign', 'members_update_scheduled', $time);
        $_MIDCOM->auth->drop_sudo();
        debug_pop();
        return true;
    }

    /**
     * Checks the parameters related to members update and returns string describing status or false if this is not
     * a smart campaign.
     * For example:
     *  - Running (started on yyyy-mm-dd H:i)
     *  - Last run on yyyy-mm-dd H:i
     *  - Last run on --, next scheduled run on --
     *  - Last run failed on --, last successful run on --
     */
    function members_update_status()
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        if (!$this->id)
        {
            debug_add('This campaign has no id (maybe not created yet?), aborting', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        if ($this->orgOpenpsaObtype != ORG_OPENPSA_OBTYPE_CAMPAIGN_SMART)
        {
            debug_add("This (id #{$this->id}) is not a smart campaign, aborting", MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        //TODO
        return false;
    }

}
?>