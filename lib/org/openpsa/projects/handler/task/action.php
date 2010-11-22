<?php
/**
 * @package org.openpsa.projects
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: action.php 23015 2009-07-28 08:50:55Z flack $
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
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_action($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();

        // Check if we get the task
        $task = new org_openpsa_projects_task_dba($args[0]);

        if (!$task)
        {
            return false;
        }

        $task->require_do('midgard:update');

        // Check if the action is a valid one
        switch ($args[1])
        {
            case 'reopen':
                org_openpsa_projects_workflow::reopen($task);
                $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
                $_MIDCOM->relocate("{$prefix}task/{$task->guid}/");
                // This will exit()

            case 'complete':
                org_openpsa_projects_workflow::complete($task);
                $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
                $_MIDCOM->relocate("{$prefix}task/{$task->guid}/");
                // This will exit()
            default:
                return false;
        }
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_action($handler_id, &$data)
    {
    }
}
?>