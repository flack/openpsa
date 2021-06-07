<?php
/**
 * @package org.openpsa.expenses
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This is the interface class for org.openpsa.expenses
 *
 * @package org.openpsa.expenses
 */
class org_openpsa_expenses_interface extends midcom_baseclasses_components_interface
implements midcom_services_permalinks_resolver
{
    /**
     * @inheritdoc
     */
    public function resolve_object_link(midcom_db_topic $topic, midcom_core_dbaobject $object) : ?string
    {
        if ($object instanceof org_openpsa_expenses_hour_report_dba) {
            return "hours/edit/{$object->guid}/";
        }
        return null;
    }

    public function _on_watched_dba_delete(midcom_core_dbaobject $object)
    {
        if (!midcom::get()->auth->request_sudo($this->_component)) {
            debug_add('Failed to get SUDO privileges, skipping task cache update silently.', MIDCOM_LOG_ERROR);
            return;
        }

        $tasks_to_update = [];

        $qb = org_openpsa_expenses_hour_report_dba::new_query_builder();
        $qb->add_constraint('invoice', '=', $object->id);
        foreach ($qb->execute() as $report) {
            $report->invoice = 0;
            $report->_skip_parent_refresh = true;
            $tasks_to_update[] = $report->task;
            if (!$report->update()) {
                debug_add("Failed to remove invoice from hour record #{$report->id}, last Midgard error was: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            }
        }

        foreach (array_unique($tasks_to_update) as $id) {
            try {
                org_openpsa_expenses_hour_report_dba::update_cache(new org_openpsa_projects_task_dba($id));
            } catch (midcom_error $e) {
                $e->log();
            }
        }

        midcom::get()->auth->drop_sudo();
    }
}
