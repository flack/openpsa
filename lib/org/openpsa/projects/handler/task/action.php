<?php
/**
 * @package org.openpsa.projects
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Task action handler
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_handler_task_action extends midcom_baseclasses_components_handler
{
    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_action($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user();

        // Check if we get the task
        $task = new org_openpsa_projects_task_dba($args[0]);
        $task->require_do('midgard:update');

        // Check if the action is a valid one
        switch ($args[1])
        {
            case 'reopen':
                org_openpsa_projects_workflow::reopen($task);
                return new midcom_response_relocate("task/{$task->guid}/");

            case 'complete':
                org_openpsa_projects_workflow::complete($task);
                return new midcom_response_relocate("task/{$task->guid}/");

            default:
                throw new midcom_error('Unknown action ' . $args[1]);
        }
    }
}
?>