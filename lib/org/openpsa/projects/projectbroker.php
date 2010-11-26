<?php
/**
 * @package org.openpsa.projects
 * @author Nemein Oy http://www.nemein.com/
 * @version $Id: projectbroker.php 22916 2009-07-15 09:53:28Z flack $
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.projects projectbroker handler
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_projectbroker
{
    var $_owner_grp = false;
    var $membership_filter = array();

    function __construct()
    {
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $owner_guid = $siteconfig->get_my_company_guid();
        if (!$owner_guid)
        {
            return false;
        }        
        $this->_owner_grp = new org_openpsa_contacts_group_dba($owner_guid);
    }

    /**
     * Does a local search for persons that match the task constraints
     *
     * @param org_openpsa_projects_task_dba &$task Task object to search prospect resources for
     * @return array of prospect persons (or false on critical failure)
     */
    function find_task_prospects(&$task)
    {
        $_MIDCOM->componentloader->load_graceful('net.nemein.tag');
        if (!class_exists('net_nemein_tag_handler'))
        {
            return false;
        }
        $return = array();
        $classes = array
        (
            'midgard_person',
            'midcom_db_person',
            'org_openpsa_contacts_person_dba',
        );
        $tag_map = net_nemein_tag_handler::get_object_tags($task);
        if (!is_array($tag_map))
        {
            // Critical failure when fetching tags, aborting
            return false;
        }
        $tags = array();
        // Resolve tasks tags (with contexts) into single array of tags without contexts
        foreach ($tag_map as $tagname => $url)
        {
            $tag = net_nemein_tag_handler::resolve_tagname($tagname);
            $tags[$tag] = $tag;
        }
        $persons = net_nemein_tag_handler::get_objects_with_tags($tags, $classes, 'AND');
        if (!is_array($persons))
        {
            return false;
        }
        // Normalize to contacts person class if necessary
        foreach ($persons as $obj)
        {
            switch (true)
            {
                case ($_MIDCOM->dbfactory->is_a($obj, 'org_openpsa_contacts_person_dba')):
                    $return[] = $obj;
                    break;
                default:
                    $tmpobj = new org_openpsa_contacts_person_dba($obj->id);
                    if (!$tmpobj->guid)
                    {
                        break;
                    }
                    $return[] = $tmpobj;
                    break;
            }
        }


        // TODO: Check other constraints (available time, country, time zone)
        $this->_find_task_prospects_filter_by_minimum_time_slot($task, $return);

        return $return;
    }

    function _find_task_prospects_filter_by_memberships(&$task, &$prospects)
    {
        static $group_cache = array();
        foreach ($prospects as $key => $person)
        {
            $qb = new midgard_query_builder('midgard_member');
            $qb->add_constraint('uid', '=', $person->id);
            $qb->begin_group('OR');
            foreach ($this->membership_filter as $guid)
            {
                if (!array_key_exists($guid, $group_cache))
                {
                    if ($guid === 'owner_group')
                    {
                        $group_cache[$guid] =& $this->_owner_grp;
                    }
                    else
                    {
                        $group_cache[$guid] = new org_openpsa_contacts_group_dba($guid);
                    }
                }
                $group =& $group_cache[$guid];
                if (!$group->id)
                {
                    // safety
                    continue;
                }
                $qb->add_constraint('gid', '=', $group->id);
            }
            $qb->end_group();
            $count = $qb->count();
            if ($count === false)
            {
                // QB error, what to do ?
                continue;
            }
            if ($count > 0)
            {
                // Is member of one of the groups required
                continue;
            }
            // Is not member in any the the groups, remove
            unset($prospects[$key]);
        }
    }

    function _find_task_prospects_filter_by_minimum_time_slot(&$task, &$prospects)
    {
        $keep_prospects = array();
        $minimum_time_slot = $task->get_parameter('org.openpsa.projects.projectbroker', 'minimum_slot');
        if (empty($minimum_time_slot))
        {
            debug_add('minimum time slot is not defined, aborting', MIDCOM_LOG_WARN);
            return;
        }
        $_MIDCOM->componentloader->load_graceful('org.openpsa.calendar');
        if (!class_exists('org_openpsa_calendar_event_participant_dba'))
        {
            debug_add('could not load org.openpsa.calendar, aborting', MIDCOM_LOG_WARN);
            return;
        }
        $_MIDCOM->auth->request_sudo('org.openpsa.projects');
        foreach ($prospects as $key => $person)
        {
            $slots = org_openpsa_calendar_event_participant_dba::find_free_times(($minimum_time_slot * 60), $person, $task->start, $task->end);
            if (   is_array($slots)
                && count($slots > 0))
            {
                $keep_prospects[$key] = true;
            }
        }
        $_MIDCOM->auth->drop_sudo();
        // Clear prospects that do not fill the time slot constraint
        debug_add('clearing prospects that do not have free time from the list');
        foreach ($prospects as $key => $person)
        {
            if (array_key_exists($key, $keep_prospects))
            {
                continue;
            }
            debug_add("removing '{$person->name}' from prospects list");
            unset($prospects[$key]);
        }
    }

    /**
     * Calls find_task_prospects and saves the results as prospects
     *
     * @param org_openpsa_projects_task_dba &$task object to search prospect resources for
     * @return boolean indicating success/failure
     */
    function save_task_prospects(&$task)
    {
        $_MIDCOM->auth->request_sudo('org.openpsa.projects');
        $task->set_parameter('org.openpsa.projects.projectbroker', 'local_search', 'SEARCH_IN_PROGRESS');
        $task->get_members();
        $prospects = $this->find_task_prospects($task);
        if (!is_array($prospects))
        {
            return false;
        }
        foreach ($prospects as $person)
        {
            if (   isset($task->resources[$person->id])
                && $task->resources[$person->id])
            {
                continue;
            }
            $prospect = new org_openpsa_projects_task_resource_dba();
            $prospect->person = $person->id;
            $prospect->task = $task->id;
            $prospect->orgOpenpsaObtype = ORG_OPENPSA_OBTYPE_PROJECTPROSPECT;
            if (!$prospect->create())
            {
                // TODO: Error reporting
            }
        }
        $task->set_parameter('org.openpsa.projects.projectbroker', 'local_search', 'SEARCH_COMPLETE');
        $_MIDCOM->auth->drop_sudo();
        return true;
    }

    /**
     * Looks for free time slots for a given person for a given task
     *
     * Does the person in question have slots of time available, what
     * are the previous and next events etc
     *
     * @parameter $person person object (alternatively ID, full person will then be loaded from DB)
     * @parameter &$task the task object to search for
     * @return array of slots
     */
    function resolve_person_timeslots($person, &$task)
    {
        $minimum_time_slot = $task->get_parameter('org.openpsa.projects.projectbroker', 'minimum_slot');
        if (empty($minimum_time_slot))
        {
            // Default to 15 minutes for minimum time here
            $minimum_time_slot = 0.25;
        }
        $_MIDCOM->componentloader->load_graceful('org.openpsa.calendar');
        if (!class_exists('org_openpsa_calendar_event_participant_dba'))
        {
            debug_add('could not load org.openpsa.calendar, aborting', MIDCOM_LOG_WARN);
            return false;
        }
        $slots = org_openpsa_calendar_event_participant_dba::find_free_times(($minimum_time_slot * 60), $person, $task->start, $task->end);
        return $slots;
    }
}

?>