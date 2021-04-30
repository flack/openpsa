<?php
/**
 * @package org.openpsa.projects
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\datamanager;

/**
 * OpenPSA group projects
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_interface extends midcom_baseclasses_components_interface
implements midcom_services_permalinks_resolver
{
    public function resolve_object_link(midcom_db_topic $topic, midcom_core_dbaobject $object) : ?string
    {
        if ($object instanceof org_openpsa_projects_project) {
            return "project/{$object->guid}/";
        }
        if ($object instanceof org_openpsa_projects_task_dba) {
            return "task/{$object->guid}/";
        }
        return null;
    }

    /**
     * Used by org_openpsa_relatedto_suspect::find_links_object to find "related to" information
     *
     * Currently handles persons
     */
    public function org_openpsa_relatedto_find_suspects(midcom_core_dbaobject $object, org_openpsa_relatedto_dba $defaults, array &$links_array)
    {
        switch (true) {
            case $object instanceof midcom_db_person:
                $this->_find_suspects_person($object, $defaults, $links_array);
                break;
            case $object instanceof org_openpsa_calendar_event_dba:
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
    private function _find_suspects_event(midcom_core_dbaobject $object, org_openpsa_relatedto_dba $defaults, array &$links_array)
    {
        if (   !is_array($object->participants)
            || count($object->participants) < 1) {
            //We have invalid list or zero participants, abort
            return;
        }
        $mc = org_openpsa_projects_task_resource_dba::new_collector();
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
        if (empty($suspects)) {
            return;
        }
        $qb = org_openpsa_projects_task_dba::new_query_builder();
        $qb->add_constraint('id', 'IN', array_unique($suspects));
        foreach ($qb->execute() as $task) {
            $to_array = ['other_obj' => false, 'link' => false];
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
    private function _find_suspects_person(midcom_core_dbaobject $object, org_openpsa_relatedto_dba $defaults, array &$links_array)
    {
        //List all projects and tasks given person is involved with
        $mc = org_openpsa_projects_task_resource_dba::new_collector('person', $object->id);
        $mc->add_constraint('task.status', '<', org_openpsa_projects_task_status_dba::COMPLETED);
        $mc->add_constraint('task.status', '<>', org_openpsa_projects_task_status_dba::DECLINED);
        $suspects = $mc->get_values('task');
        if (empty($suspects)) {
            return;
        }
        $qb = org_openpsa_projects_task_dba::new_query_builder();
        $qb->add_constraint('id', 'IN', array_unique($suspects));
        foreach ($qb->execute() as $task) {
            $to_array = ['other_obj' => false, 'link' => false];
            $link = new org_openpsa_relatedto_dba();
            org_openpsa_relatedto_suspect::defaults_helper($link, $defaults, $this->_component, $task);
            $to_array['other_obj'] = $task;
            $to_array['link'] = $link;

            $links_array[] = $to_array;
        }
    }

    /**
     * Prepare the indexer client
     */
    public function _on_reindex($topic, midcom_helper_configuration $config, midcom_services_indexer $indexer)
    {
        $qb_tasks = org_openpsa_projects_task_dba::new_query_builder();
        $dm_tasks = datamanager::from_schemadb($config->get('schemadb_task'));

        $qb_projects = org_openpsa_projects_project::new_query_builder();
        $dm_projects = datamanager::from_schemadb($config->get('schemadb_project'));

        $indexer = new org_openpsa_projects_midcom_indexer($topic, $indexer);
        $indexer->add_query('tasks', $qb_tasks, $dm_tasks);
        $indexer->add_query('projects', $qb_projects, $dm_projects);

        return $indexer;
    }
}
