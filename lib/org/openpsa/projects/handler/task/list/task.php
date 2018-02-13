<?php
/**
 * @package org.openpsa.projects
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Subtasks handler
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_handler_task_list_task extends org_openpsa_projects_handler_task_list
{
    protected $show_status_controls = true;

    protected $is_single_project = true;

    protected $show_customer = false;

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
        $this->prepare_request_data('subtasks');

        $data['task'] = new org_openpsa_projects_task_dba($args[0]);

        $this->qb = org_openpsa_projects_task_dba::new_query_builder();
        $this->qb->add_constraint('up', '=', $this->_request_data['task']->id);
        $this->add_filters('task');
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_list($handler_id, array &$data)
    {
        midcom_show_style('show-task-grid');
    }
}
