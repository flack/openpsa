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
class org_openpsa_directmarketing_campaign_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_campaign';

    const TYPE_NORMAL = 9500;
    const TYPE_SMART = 9501;

    /**
     * List of tests members (stored as campaign_members, referenced here for easier access)
     *
     * @var array
     */
    public $testers = array();

    /**
     * Rules for smart-campaign
     *
     * @var array
     */
    public $rules = array();

    public function __construct($id = null)
    {
        parent::__construct($id);
        if (!$this->orgOpenpsaObtype)
        {
            $this->orgOpenpsaObtype = self::TYPE_NORMAL;
        }
    }

    public function _on_loaded()
    {
        $this->_unserialize_rules();
        if (!is_array($this->rules))
        {
            $this->rules = array();
        }
    }

    public function _on_creating()
    {
        $this->_serialize_rules();
        return true;
    }

    public function _on_updating()
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
            debug_add('This campaign has no id (maybe not created yet?), aborting', MIDCOM_LOG_ERROR);
            return false;
        }
        $mc = org_openpsa_directmarketing_campaign_member_dba::new_collector('campaign', $this->id);
        $mc->add_constraint('orgOpenpsaObtype', '=', org_openpsa_directmarketing_campaign_member_dba::TESTER);
        $testers = $mc->get_values('person');

        //Just to be sure
        $this->testers = array();
        foreach ($testers as $tester)
        {
            $this->testers[$tester] = true;
        }
    }

    /**
     * Unserializes rulesSerialized to rules
     */
    private function _unserialize_rules()
    {
        $unserRet = @unserialize($this->rulesSerialized);
        if ($unserRet === false)
        {
            //Unserialize failed (probably newline/encoding issue), try to fix the serialized string and unserialize again
            $unserRet = @unserialize(midcom_helper_misc::fix_serialization($this->rulesSerialized));
            if ($unserRet === false)
            {
                debug_add('Failed to unserialize rulesSerialized', MIDCOM_LOG_WARN);
                $this->rules = array();
                return;
            }
        }
        $this->rules = $unserRet;
    }

    /**
     * Serializes rules to rulesSerialized
     */
    private function _serialize_rules()
    {
        $this->rulesSerialized = serialize($this->rules);
    }

    /**
     * Creates/Removes members for this smart campaign based on the rules array
     * NOTE: This is highly resource intensive for large campaigns
     *
     * @return boolean indicating success/failure
     */
    function update_smart_campaign_members()
    {
        midcom::get()->disable_limits();
        if (!$this->id)
        {
            debug_add('This campaign has no id (maybe not created yet?), aborting', MIDCOM_LOG_ERROR);
            return false;
        }
        if ($this->orgOpenpsaObtype != self::TYPE_SMART)
        {
            debug_add("This (id #{$this->id}) is not a smart campaign, aborting", MIDCOM_LOG_ERROR);
            return false;
        }
        midcom::get('auth')->request_sudo('org.openpsa.directmarketing');
        $this->parameter('org.openpsa.directmarketing_smart_campaign', 'members_update_failed', '');
        $this->parameter('org.openpsa.directmarketing_smart_campaign', 'members_update_started', time());

        $solver = new org_openpsa_directmarketing_campaign_ruleresolver();
        if (!$solver->resolve($this->rules))
        {
            $this->parameter('org.openpsa.directmarketing_smart_campaign', 'members_update_failed', time());
            debug_add('Failed to resolve rules', MIDCOM_LOG_ERROR);
            debug_print_r("this->rules has value:", $this->rules);
            midcom::get('auth')->drop_sudo();
            return false;
        }
        //returns now the result array of collector instead array of objects of query builder
        $rule_persons =  $solver->execute();
        if (!is_array($rule_persons))
        {
            $this->parameter('org.openpsa.directmarketing_smart_campaign', 'members_update_failed', time());
            debug_add('Failure when executing rules based search', MIDCOM_LOG_ERROR);
            midcom::get('auth')->drop_sudo();
            return false;
        }

        //Delete (normal) members that should not be here anymore
        $qb_unwanted = org_openpsa_directmarketing_campaign_member_dba::new_query_builder();
        $qb_unwanted->add_constraint('campaign', '=', $this->id);
        $qb_unwanted->add_constraint('orgOpenpsaObtype', '=', org_openpsa_directmarketing_campaign_member_dba::NORMAL);

        if (sizeof($rule_persons) > 0)
        {
            $qb_unwanted->add_constraint('person', 'NOT IN', array_keys($rule_persons));
        }

        $uwret = $qb_unwanted->execute();
        if (   is_array($uwret)
            && !empty($uwret))
        {
            foreach ($uwret as $member)
            {
                debug_add("Deleting unwanted member #{$member->id} (linked to person #{$member->person}) in campaign #{$this->id}");
                if (!$member->delete())
                {
                    debug_add("Failed to delete unwanted member #{$member->id} (linked to person #{$member->person}) in campaign #{$this->id}, reason: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                }
            }
        }

        //List current non-tester members (including unsubscribed etc), and filter those out of rule_persons
        $mc_current = org_openpsa_directmarketing_campaign_member_dba::new_collector('campaign', $this->id);
        $mc_current->add_constraint('orgOpenpsaObtype', '<>', org_openpsa_directmarketing_campaign_member_dba::TESTER);
        $current = $mc_current->get_values('person');
        if (!empty($current))
        {
            $rule_persons = array_diff_key($rule_persons, array_flip($current));
        }

        //Finally, create members of each person matched by rule left
        reset ($rule_persons);
        foreach ($rule_persons as $id => $person)
        {
            debug_add("Creating new member (linked to person #{$id}) to campaign #{$this->id}");
            $member = new org_openpsa_directmarketing_campaign_member_dba();
            $member->orgOpenpsaObtype = org_openpsa_directmarketing_campaign_member_dba::NORMAL;
            $member->campaign = $this->id;
            $member->person = $id;
            if (!$member->create())
            {
                debug_add("Failed to create new member (linked to person #{$id}) in campaign #{$this->id}, reason: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            }
        }

        //All done, set last updated timestamp
        $this->parameter('org.openpsa.directmarketing_smart_campaign', 'members_updated', time());

        midcom::get('auth')->drop_sudo();
        return true;
    }

    /**
     * Schedules a background memberships update for a smart campaign
     */
    function schedule_update_smart_campaign_members($time = false)
    {
        if (!$time)
        {
            $time = time();
        }
        if (!$this->id)
        {
            debug_add('This campaign has no id (maybe not created yet?), aborting', MIDCOM_LOG_ERROR);
            return false;
        }
        if ($this->orgOpenpsaObtype != self::TYPE_SMART)
        {
            debug_add("This (id #{$this->id}) is not a smart campaign, aborting", MIDCOM_LOG_ERROR);
            return false;
        }
        midcom::get('auth')->request_sudo('org.openpsa.directmarketing');
        $stat = midcom_services_at_interface::register($time, 'org.openpsa.directmarketing', 'background_update_campaign_members', array('campaign_guid' => $this->guid));
        if (!$stat)
        {
            debug_add('Failed to register an AT job for members update, errstr: ' . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            midcom::get('auth')->drop_sudo();
            return false;
        }
        $this->parameter('org.openpsa.directmarketing_smart_campaign', 'members_update_scheduled', $time);
        midcom::get('auth')->drop_sudo();
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
        if (!$this->id)
        {
            debug_add('This campaign has no id (maybe not created yet?), aborting', MIDCOM_LOG_ERROR);
            return false;
        }
        if ($this->orgOpenpsaObtype != self::TYPE_SMART)
        {
            debug_add("This (id #{$this->id}) is not a smart campaign, aborting", MIDCOM_LOG_ERROR);
            return false;
        }
        return false;
    }
}
?>