<?php
/**
 * @package org.openpsa.projects
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Request;

/**
 * Subtasks handler
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_handler_task_list_task extends org_openpsa_projects_handler_task_list
{
    protected bool $show_status_controls = true;

    protected bool $is_single_project = true;

    protected bool $show_customer = false;

    public function _handler_list(Request $request, array $args)
    {
        $this->prepare_request_data('subtasks');

        $task = new org_openpsa_projects_task_dba($args[0]);

        $this->qb = org_openpsa_projects_task_dba::new_query_builder();
        $this->qb->add_constraint('up', '=', $task->id);
        $this->add_filters('task', $request);

        return $this->show('show-task-grid');
    }
}
