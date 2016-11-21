<?php
/**
 * @package org.openpsa.projects
 * @author Nemein Oy http://www.nemein.com/
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
    /**
     * Does a local search for persons that match the task constraints
     *
     * @param org_openpsa_projects_task_dba $task Task object to search prospect resources for
     * @return org_openpsa_contacts_person_dba[] Array of prospect persons
     */
    function find_task_prospects($task)
    {
        $return = array();
        $classes = array(
            'midgard_person',
            'midcom_db_person',
            'org_openpsa_contacts_person_dba',
        );
        $tag_map = net_nemein_tag_handler::get_object_tags($task);

        $tags = array();
        // Resolve tasks tags (with contexts) into single array of tags without contexts
        foreach (array_keys($tag_map) as $tagname) {
            $tag = net_nemein_tag_handler::resolve_tagname($tagname);
            $tags[$tag] = $tag;
        }
        $persons = net_nemein_tag_handler::get_objects_with_tags($tags, $classes, 'AND');
        // Normalize to contacts person class if necessary and filter out existing resources
        $task->get_members();
        foreach ($persons as $obj) {
            if (!empty($task->resources[$obj->id])) {
                continue;
            }
            if (!$obj instanceof org_openpsa_contacts_person_dba) {
                try {
                    $obj = new org_openpsa_contacts_person_dba($obj->id);
                } catch (midcom_error $e) {
                    $e->log();
                    continue;
                }
            }
            $return[] = $obj;
        }

        // TODO: Check other constraints (available time, country, time zone)
        $this->_find_task_prospects_filter_by_minimum_time_slot($task, $return);

        return $return;
    }

    private function _find_task_prospects_filter_by_minimum_time_slot($task, array &$prospects)
    {
        $minimum_time_slot = $task->get_parameter('org.openpsa.projects.projectbroker', 'minimum_slot');
        if (empty($minimum_time_slot)) {
            debug_add('minimum time slot is not defined, aborting', MIDCOM_LOG_WARN);
            return;
        }

        debug_add('clearing prospects that do not have free time from the list');
        midcom::get()->auth->request_sudo('org.openpsa.projects');
        foreach ($prospects as $key => $person) {
            $slots = org_openpsa_calendar_event_member_dba::find_free_times(($minimum_time_slot * 60), $person, $task->start, $task->end);
            if (empty($slots)) {
                debug_add("removing '{$person->name}' from prospects list");
                unset($prospects[$key]);
            }
        }
        midcom::get()->auth->drop_sudo();
    }

    /**
     * Calls find_task_prospects and saves the results as prospects
     *
     * @param org_openpsa_projects_task_dba $task object to search prospect resources for
     * @return boolean indicating success/failure
     */
    public function save_task_prospects($task)
    {
        midcom::get()->auth->request_sudo('org.openpsa.projects');
        $task->set_parameter('org.openpsa.projects.projectbroker', 'local_search', 'SEARCH_IN_PROGRESS');
        $prospects = $this->find_task_prospects($task);

        foreach ($prospects as $person) {
            $prospect = new org_openpsa_projects_task_resource_dba();
            $prospect->person = $person->id;
            $prospect->task = $task->id;
            $prospect->orgOpenpsaObtype = org_openpsa_projects_task_resource_dba::PROSPECT;
            if (!$prospect->create()) {
                debug_add('Failed to create prospect: ' . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            }
        }
        $task->set_parameter('org.openpsa.projects.projectbroker', 'local_search', 'SEARCH_COMPLETE');
        midcom::get()->auth->drop_sudo();
        return true;
    }

    /**
     * Looks for free time slots for a given person for a given task
     *
     * Does the person in question have slots of time available, what
     * are the previous and next events etc
     *
     * @param $person person object (alternatively ID, full person will then be loaded from DB)
     * @param $task the task object to search for
     * @return array of slots
     */
    public function resolve_person_timeslots($person, $task)
    {
        $minimum_time_slot = $task->get_parameter('org.openpsa.projects.projectbroker', 'minimum_slot');
        if (empty($minimum_time_slot)) {
            // Default to 15 minutes for minimum time here
            $minimum_time_slot = 0.25;
        }
        return org_openpsa_calendar_event_member_dba::find_free_times(($minimum_time_slot * 60), $person, $task->start, $task->end);
    }
}
