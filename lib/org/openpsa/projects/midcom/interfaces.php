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
