<?php
/**
 * @package org.openpsa.projects
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * OpenPSA group projects
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_interface extends midcom_baseclasses_components_interface
implements midcom_services_permalinks_resolver
{
    public function resolve_object_link(midcom_db_topic $topic, midcom_core_dbaobject $object)
    {
        if ($object instanceof org_openpsa_projects_project)
        {
            return "project/{$object->guid}/";
        }
        if ($object instanceof org_openpsa_projects_task_dba)
        {
            return "task/{$object->guid}/";
        }
        return null;
    }

    /**
     * Used by org_openpsa_relatedto_suspect::find_links_object to find "related to" information
     *
     * Currently handles persons
     */
    public function org_openpsa_relatedto_find_suspects(midcom_core_dbaobject $object, $defaults, array &$links_array)
    {
        switch (true)
        {
            case midcom::get()->dbfactory->is_a($object, 'midcom_db_person'):
                $this->_find_suspects_person($object, $defaults, $links_array);
                break;
            case midcom::get()->dbfactory->is_a($object, 'midcom_db_event'):
            case midcom::get()->dbfactory->is_a($object, 'org_openpsa_calendar_event_dba'):
                $this->_find_suspects_event($object, $defaults, $links_array);
                break;

                //TODO: groups ? other objects ?
        }
    }

    /**
     * Used by org_openpsa_relatedto_find_suspects to in case the given object is a person
     *
     * Current rule: all participants of event must be either manager, contact or resource in task
     * that overlaps in time with the event.
     */
    private function _find_suspects_event(midcom_core_dbaobject $object, $defaults, array &$links_array)
    {
        if (   !is_array($object->participants)
            || count($object->participants) < 1)
        {
            //We have invalid list or zero participants, abort
            return;
        }
        $mc = org_openpsa_projects_task_resource_dba::new_collector('metadata.deleted', false);
        //Target task starts or ends inside given events window or starts before and ends after
        $mc->add_constraint('task.start', '<=', $object->end);
        $mc->add_constraint('task.end', '>=', $object->start);
        //Target task is active
        $mc->add_constraint('task.status', '<', org_openpsa_projects_task_status_dba::COMPLETED);
        $mc->add_constraint('task.status', '<>', org_openpsa_projects_task_status_dba::DECLINED);
        //Each event participant is either manager or member (resource/contact) in task
        $mc->begin_group('OR');
            $mc->add_constraint('task.manager', 'IN', array_keys($object->participants));
            $mc->add_constraint('person', 'IN', array_keys($object->participants));
        $mc->end_group();
        $suspects = $mc->get_values('task');
        if (empty($suspects))
        {
            return;
        }
        $qb = org_openpsa_projects_task_dba::new_query_builder();
        $qb->add_constraint('id', 'IN', array_unique($suspects));
        $tasks = $qb->execute();
        foreach ($tasks as $task)
        {
            $to_array = array('other_obj' => false, 'link' => false);
            $link = new org_openpsa_relatedto_dba();
            org_openpsa_relatedto_suspect::defaults_helper($link, $defaults, $this->_component, $task);
            $to_array['other_obj'] = $task;
            $to_array['link'] = $link;

            $links_array[] = $to_array;
        }
    }

    /**
     * Used by org_openpsa_relatedto_find_suspects to in case the given object is a person
     */
    private function _find_suspects_person(midcom_core_dbaobject $object, $defaults, array &$links_array)
    {
        //List all projects and tasks given person is involved with
        $mc = org_openpsa_projects_task_resource_dba::new_collector('person', $object->id);
        $mc->add_constraint('task.status', '<', org_openpsa_projects_task_status_dba::COMPLETED);
        $mc->add_constraint('task.status', '<>', org_openpsa_projects_task_status_dba::DECLINED);
        $suspects = $mc->get_values('task');
        if (empty($suspects))
        {
            return;
        }
        $qb = org_openpsa_projects_task_dba::new_query_builder();
        $qb->add_constraint('id', 'IN', array_unique($suspects));
        $tasks = $qb->execute();
        foreach ($tasks as $task)
        {
            $to_array = array('other_obj' => false, 'link' => false);
            $link = new org_openpsa_relatedto_dba();
            org_openpsa_relatedto_suspect::defaults_helper($link, $defaults, $this->_component, $task);
            $to_array['other_obj'] = $task;
            $to_array['link'] = $link;

            $links_array[] = $to_array;
        }
    }

    function create_hour_report(org_openpsa_projects_task_dba $task, $person_id, $from_object, $from_component)
    {
        if (empty($person_id))
        {
            debug_add('person_id is "empty"', MIDCOM_LOG_ERROR);
            return false;
        }

        //TODO: this should probably have privileges like midgard:owner set to $person_id
        $hr = new org_openpsa_projects_hour_report_dba();
        $hr->task = $task->id;
        $hr->person = $person_id;
        $hr->invoiceable = $task->hoursInvoiceableDefault;

        switch (true)
        {
            case midcom::get()->dbfactory->is_a($from_object, 'org_openpsa_calendar_event_dba'):
                $event = $from_object;
                $hr->date = $event->start;
                $hr->hours = round((($event->end - $event->start) / 3600), 2);
                // TODO: Localize ? better indicator that this is indeed from event ??
                $hr->description = "event: {$event->title} " . $this->_l10n->get_formatter()->timeframe($event->start, $event->end) . ", {$event->location}\n";
                $hr->description .= "\n{$event->description}\n";
                break;
            default:
                debug_add("class '" . get_class($from_object) . "' not supported", MIDCOM_LOG_ERROR);
                return false;
        }
        debug_print_r("about to create hour_report", $hr);

        if (!$hr->create())
        {
            debug_add("failed to create hour_report to task #{$task->id} for person #{$person_id}", MIDCOM_LOG_ERROR);
            return false;
        }
        debug_add("created hour_report #{$hr->id}");

        // Create a relatedtolink from hour_report to the object it was created from
        org_openpsa_relatedto_plugin::create($hr, 'org.openpsa.projects', $from_object, $from_component);

        return true;
    }

    function background_search_resources($args, $handler)
    {
        try
        {
            $task = new org_openpsa_projects_task_dba($args['task']);
        }
        catch (midcom_error $e)
        {
            $e->log();
            return false;
        }
        $broker = new org_openpsa_projects_projectbroker();
        return $broker->save_task_prospects($task);
    }

    /**
     * Prepare the indexer client
     */
    public function _on_reindex($topic, $config, &$indexer)
    {
        $qb_tasks = org_openpsa_projects_task_dba::new_query_builder();
        $schemadb_tasks = midcom_helper_datamanager2_schema::load_database($config->get('schemadb_task'));

        $qb_projects = org_openpsa_projects_project::new_query_builder();
        $schemadb_projects = midcom_helper_datamanager2_schema::load_database($config->get('schemadb_project'));

        $indexer = new org_openpsa_projects_midcom_indexer($topic, $indexer);
        $indexer->add_query('tasks', $qb_tasks, $schemadb_tasks);
        $indexer->add_query('projects', $qb_projects, $schemadb_projects);

        return $indexer;
    }
}
