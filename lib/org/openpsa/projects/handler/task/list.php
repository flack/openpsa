<?php
/**
 * @package org.openpsa.projects
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: list.php 26679 2010-10-04 16:25:19Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Task list handler
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_handler_task_list extends midcom_baseclasses_components_handler
{
    var $_task_cache = Array();

    function _on_initialize()
    {
        $_MIDCOM->load_library('org.openpsa.contactwidget');
    }

    /**
     * Add a task to a requested task list view
     *
     * @param integer $task_id ID of an org_openpsa_projects_task_dba object
     * @param string $list Key of the task list
     * @return boolean
     */
    function _add_task_to_list($task_id, $list = 'current')
    {
        if (!$task_id)
        {
            return false;
        }
        // Ensure the requested list is available
        if (!array_key_exists($list, $this->_request_data['tasks']))
        {
            $this->_request_data['tasks'][$list] = Array();
        }

        // Instantiate each task only once
        if (!array_key_exists($task_id, $this->_task_cache))
        {
            $this->_task_cache[$task_id] = new org_openpsa_projects_task_dba($task_id);
        }

        // Only accept tasks to this list, projects need not apply
        if ($this->_task_cache[$task_id]->orgOpenpsaObtype != ORG_OPENPSA_OBTYPE_TASK)
        {
            return false;
        }

        // Add task to a list only once
        if (!array_key_exists($task_id, $this->_request_data['tasks'][$list]))
        {
            $this->_request_data['tasks'][$list][$task_id] = $this->_task_cache[$task_id];
        }
        return true;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_list($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();
        $this->_request_data['tasks'] = Array();

        //get possible priorities from schema
        $this->_get_priorities();

        if (count($args) > 0)
        {
            switch ($args[0])
            {
                //for json no style is needed
                case 'all':
                    $this->_prepare_output();
                    org_openpsa_core_ui::enable_jqgrid();
                    return $this->_handler_list_all($args);
                    break;
                case 'project':
                    $this->_prepare_output();

                    $_MIDCOM->add_link_head
                    (
                        array
                        (
                            'rel' => 'stylesheet',
                            'type' => 'text/css',
                            'href' => MIDCOM_STATIC_URL . "/org.openpsa.core/list.css",
                        )
                    );

                    return $this->_handler_list_project($args);
                    break;
                case 'json':
                    $return = $this->_handler_list_project($args);
                    //form of tasks has to be changed for json
                    $this->_change_tasks_for_json();
                    $_MIDCOM->skip_page_style = true;
                    $this->_request_data['view'] = 'json';
                    return $return;
                default:
                    return false;
            }
        }
        else
        {
            return $this->_handler_list_user();
        }
        return false;
    }

    private function _handler_list_user()
    {
        // Query user's current tasks
        $this->_request_data['view'] = 'my_tasks';

        // Tasks proposed to the user
        $mc = org_openpsa_projects_task_resource_dba::new_collector('person', midcom_connection::get_user());
        $mc->add_value_property('task');
        $mc->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_PROJECTRESOURCE);
        $mc->add_constraint('task.orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_TASK);
        $mc->add_constraint('task.status', '=', ORG_OPENPSA_TASKSTATUS_PROPOSED);
        $mc->add_order('task.priority', 'ASC');
        // Workgroup filtering
        if ($GLOBALS['org_openpsa_core_workgroup_filter'] != 'all')
        {
            $mc->add_constraint('task.orgOpenpsaOwnerWg', '=', $GLOBALS['org_openpsa_core_workgroup_filter']);
        }
        $mc->execute();
        $ret = $mc->list_keys();

        if (   is_array($ret)
            && count($ret) > 0)
        {
            foreach ($ret as $guid => $resource)
            {
                $this->_add_task_to_list($mc->get_subkey($guid, 'task'), 'proposed');
            }
        }

        // Tasks user has under work
        $mc = org_openpsa_projects_task_resource_dba::new_collector('person', midcom_connection::get_user());
        $mc->add_value_property('task');
        $mc->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_PROJECTRESOURCE);
        $mc->add_constraint('task.orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_TASK);
        $mc->begin_group('OR');
            $mc->begin_group('AND');
                $mc->add_constraint('task.status', '>=', ORG_OPENPSA_TASKSTATUS_STARTED);
                $mc->add_constraint('task.status', '<', ORG_OPENPSA_TASKSTATUS_COMPLETED);
            $mc->end_group();
            $mc->add_constraint('task.status', '=', ORG_OPENPSA_TASKSTATUS_ACCEPTED);
        $mc->end_group();
        $mc->add_order('task.priority', 'ASC');
        // Workgroup filtering
        if ($GLOBALS['org_openpsa_core_workgroup_filter'] != 'all')
        {
            $mc->add_constraint('task.orgOpenpsaOwnerWg', '=', $GLOBALS['org_openpsa_core_workgroup_filter']);
        }
        $mc->execute();
        $ret = $mc->list_keys();

        if (   is_array($ret)
            && count($ret) > 0)
        {
            foreach ($ret as $guid => $resource)
            {
                $this->_add_task_to_list($mc->get_subkey($guid, 'task'), 'current');
            }
        }

        // Tasks completed by user and pending approval
        $mc = org_openpsa_projects_task_resource_dba::new_collector('person', midcom_connection::get_user());
        $mc->add_value_property('task');
        $mc->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_PROJECTRESOURCE);
        $mc->add_constraint('task.orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_TASK);
        $mc->add_constraint('task.status', '=', ORG_OPENPSA_TASKSTATUS_COMPLETED);
        $mc->add_order('task.priority', 'ASC');
        // Workgroup filtering
        if ($GLOBALS['org_openpsa_core_workgroup_filter'] != 'all')
        {
            $mc->add_constraint('task.orgOpenpsaOwnerWg', '=', $GLOBALS['org_openpsa_core_workgroup_filter']);
        }
        $mc->execute();
        $ret = $mc->list_keys();
        if (   is_array($ret)
            && count($ret) > 0)
        {
            foreach ($ret as $guid => $resource)
            {
                $this->_add_task_to_list($mc->get_subkey($guid, 'task'), 'completed');
            }
        }

        // Tasks user is manager of that are pending acceptance
        $qb = org_openpsa_projects_task_dba::new_query_builder();
        $qb->add_constraint('status', '=', ORG_OPENPSA_TASKSTATUS_PROPOSED);
        $qb->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_TASK);
        $qb->add_constraint('manager', '=', midcom_connection::get_user());
        $qb->add_order('priority', 'ASC');
        // Workgroup filtering
        if ($GLOBALS['org_openpsa_core_workgroup_filter'] != 'all')
        {
            $qb->add_constraint('orgOpenpsaOwnerWg', '=', $GLOBALS['org_openpsa_core_workgroup_filter']);
        }
        $ret = $qb->execute();
        if (   is_array($ret)
            && count($ret) > 0)
        {
            foreach ($ret as $task)
            {
                if (!isset($this->_task_cache[$task->id]))
                {
                    $this->_task_cache[$task->id] = $task;
                }
                $this->_add_task_to_list($task->id, 'pending_accept');
            }
        }

        // Tasks user is manager of that are have been declined by all resources
        $qb = org_openpsa_projects_task_dba::new_query_builder();
        $qb->add_constraint('status', '=', ORG_OPENPSA_TASKSTATUS_DECLINED);
        $qb->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_TASK);
        $qb->add_constraint('manager', '=', midcom_connection::get_user());
        $qb->add_order('priority', 'ASC');
        // Workgroup filtering
        if ($GLOBALS['org_openpsa_core_workgroup_filter'] != 'all')
        {
            $qb->add_constraint('orgOpenpsaOwnerWg', '=', $GLOBALS['org_openpsa_core_workgroup_filter']);
        }
        $ret = $qb->execute();
        if (   is_array($ret)
            && count($ret) > 0)
        {
            foreach ($ret as $task)
            {
                if (!isset($this->_task_cache[$task->id]))
                {
                    $this->_task_cache[$task->id] = $task;
                }
                $this->_add_task_to_list($task->id, 'declined');
            }
        }

        // Tasks user is manager of that are pending approval
        $qb = org_openpsa_projects_task_dba::new_query_builder();
        $qb->add_constraint('status', '=', ORG_OPENPSA_TASKSTATUS_COMPLETED);
        $qb->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_TASK);
        $qb->add_constraint('manager', '=', midcom_connection::get_user());
        $qb->add_order('priority', 'ASC');
        // Workgroup filtering
        if ($GLOBALS['org_openpsa_core_workgroup_filter'] != 'all')
        {
            $qb->add_constraint('orgOpenpsaOwnerWg', '=', $GLOBALS['org_openpsa_core_workgroup_filter']);
        }
        $ret = $qb->execute();
        if (   is_array($ret)
            && count($ret) > 0)
        {
            foreach ($ret as $task)
            {
                if (!isset($this->_task_cache[$task->id]))
                {
                    $this->_task_cache[$task->id] = $task;
                }
                $this->_add_task_to_list($task->id, 'pending_approve');
            }
        }

        // Tasks user is manager of that are on hold
        $qb = org_openpsa_projects_task_dba::new_query_builder();
        $qb->add_constraint('status', '=', ORG_OPENPSA_TASKSTATUS_ONHOLD);
        $qb->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_TASK);
        $qb->add_constraint('manager', '=', midcom_connection::get_user());
        $qb->add_order('priority', 'ASC');
        // Workgroup filtering
        if ($GLOBALS['org_openpsa_core_workgroup_filter'] != 'all')
        {
            $qb->add_constraint('orgOpenpsaOwnerWg', '=', $GLOBALS['org_openpsa_core_workgroup_filter']);
        }
        $ret = $qb->execute();
        if (   is_array($ret)
            && count($ret) > 0)
        {
            foreach ($ret as $task)
            {
                if (!isset($this->_task_cache[$task->id]))
                {
                    $this->_task_cache[$task->id] = $task;
                }
                $this->_add_task_to_list($task->id, 'onhold');
            }
        }

        return true;
    }

    private function _handler_list_project(&$args)
    {
        $this->_request_data['project'] = new org_openpsa_projects_project($args[1]);
        if (!$this->_request_data['project'])
        {
            return false;
        }

        // Query tasks of a project
        $this->_request_data['view'] = 'project_tasks';

        $qb = org_openpsa_projects_task_dba::new_query_builder();
        $qb->add_constraint('up', '=', $this->_request_data['project']->id);
        $qb->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_TASK);
        //When we have the read-only link to object status etc use those to narrow this down
        $qb->add_order('priority', 'ASC');
        $qb->add_order('end', 'DESC');
        $qb->add_order('start', 'DESC');
        $qb->add_order('title');

        //don't filter for json'
        if($args[0] != 'json')
        {
            //array with filter options
            $filters = array
            (
                "priority"
            );
            $priority_filter = new org_openpsa_core_filter($filters, $qb , '<=' ,$this->_request_data['priority_array']);
            $this->_request_data["filter_priority"] = $priority_filter->list_filter("priority");
        }

        $ret = $qb->execute();
        $this->_request_data['tasks'] = array
        (
           'proposed' => array(),
           'current' => array(),
           'completed' => array(),
           'closed' => array(),
        );
        if (   is_array($ret)
            && count($ret) > 0)
        {
            foreach($ret as $task)
            {
                switch ($task->status)
                {
                    case ORG_OPENPSA_TASKSTATUS_PROPOSED:
                        $list = 'proposed';
                        break;
                    case ORG_OPENPSA_TASKSTATUS_ACCEPTED:
                    case ORG_OPENPSA_TASKSTATUS_STARTED:
                    case ORG_OPENPSA_TASKSTATUS_REJECTED:
                    case ORG_OPENPSA_TASKSTATUS_REOPENED:
                    default:
                        $list = 'current';
                        break;
                    case ORG_OPENPSA_TASKSTATUS_COMPLETED:
                        $list = 'completed';
                        break;
                    case ORG_OPENPSA_TASKSTATUS_APPROVED:
                    case ORG_OPENPSA_TASKSTATUS_CLOSED:
                        $list = 'closed';
                        break;
                }
                $this->_request_data['tasks'][$list][$task->id] = $task;
            }
        }
        return true;
    }

    /**
     * List all tasks, optionally filtered by status
     */
    private function _handler_list_all($args)
    {
        $qb = org_openpsa_projects_task_dba::new_query_builder();
        $qb->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_TASK);
        $qb->add_order('priority', 'ASC');
        // Default to open tasks list if none specified
        if (   !isset($args[1])
            || empty($args[1]))
        {
            $args[1] = 'open';
        }
        switch ($args[1])
        {
            case 'agreement':
                if (!$args[2])
                {
                    return false;
                }
                $agreement_id = (int) $args[2];
                $this->_request_data['agreement'] = $agreement_id;
                $qb->add_constraint('agreement', '=', $agreement_id);
                $qb->add_order('end', 'DESC');
                break;
            case 'all':
            case 'both':
                $args[1] = 'all';
                $qb->add_order('end', 'DESC');
                break;
            case 'open':
                $this->_component_data['active_leaf'] = "{$this->_topic->id}:tasks_open";
                $qb->add_constraint('status', '<', ORG_OPENPSA_TASKSTATUS_CLOSED);
                $qb->add_order('end');
                break;
            case 'closed':
                $this->_component_data['active_leaf'] = "{$this->_topic->id}:tasks_closed";
                $qb->add_constraint('status', '=', ORG_OPENPSA_TASKSTATUS_CLOSED);
                $qb->add_order('end', 'DESC');
                break;
            case 'current':
                $qb->add_constraint
                (
                    'status',
                    'IN',
                    array
                    (
                        ORG_OPENPSA_TASKSTATUS_ACCEPTED,
                        ORG_OPENPSA_TASKSTATUS_STARTED,
                        ORG_OPENPSA_TASKSTATUS_REJECTED,
                        ORG_OPENPSA_TASKSTATUS_REOPENED
                    )
                );
                $qb->add_order('end');
                break;
            case 'invoiceable':
                $this->_component_data['active_leaf'] = "{$this->_topic->id}:tasks_invoiceable";
                $qb->add_constraint('invoiceableHours', '>', 0);
                $qb->add_order('end');
                break;
            case 'invoiced':
                $this->_component_data['active_leaf'] = "{$this->_topic->id}:tasks_invoiced";
                $qb->add_constraint('invoicedHours', '>', 0);
                $qb->add_order('end', 'DESC');
                break;
            default:
                debug_add("Filter {$args[1]} not recognized", MIDCOM_LOG_ERROR);
                return false;
                break;
        }
        $qb->add_order('customer');
        $qb->add_order('up');
        $qb->add_order('title');

        //array with filter options
        $filters = array
        (
            "priority"
        );
        $priority_filter = new org_openpsa_core_filter($filters, $qb , '<=' ,$this->_request_data['priority_array']);
        $this->_request_data["filter_priority"] = $priority_filter->list_filter("priority");

        $this->_request_data['table-heading'] = $args[1] . ' tasks';
        $this->_request_data['view_identifier'] = $args[1];
        $tasks = $qb->execute();
        if ($tasks === false)
        {
            return false;
        }
        $this->_request_data['view'] = 'grid';
        $this->_request_data['tasks'] = $tasks;

        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_list($handler_id, &$data)
    {
        switch ($data['view'])
        {
            case 'json':
                midcom_show_style('show-json-tasks');
                break;
            case 'grid':
                if ($data['view_identifier'] != 'agreement')
                {
                    midcom_show_style("show-priority-filter");
                }
                $data['handler'] = $this;

                midcom_show_style("show-task-grid");
                break;
            default:
                midcom_show_style("show-priority-filter");
                if (count($this->_request_data['tasks']) > 0)
                {
                    midcom_show_style("show-tasks-header");
                    foreach ($this->_request_data['tasks'] as $list_type => $tasks)
                    {
                        if (count($tasks) == 0)
                        {
                            // No tasks, skip this category
                            continue;
                        }

                        $this->_request_data['list_type'] = $list_type;

                        midcom_show_style("show-tasks-list-header");

                        $data['class'] = 'even';
                        foreach ($tasks as $task)
                        {
                            $this->_request_data['task'] =& $task;
                            midcom_show_style("show-tasks-{$list_type}-item");
                            if ($data['class'] == 'even')
                            {
                                $data['class'] = 'odd';
                            }
                            else
                            {
                                $data['class'] = 'even';
                            }
                        }

                        midcom_show_style("show-tasks-list-footer");

                    }
                    midcom_show_style("show-tasks-footer");
                }
                break;
        }
    }

    /**
     * Helper to get the relevant data for cells in table view
     */
    public function get_table_row_data(&$task, &$data)
    {
        $ret = array();

        static $row_cache = array
        (
            'parent' => array(),
            'index_parent' => array(),
            'customer' => array(),
            'index_customer' => array(),
            'agreement' => array(),
            'index_agreement' => array(),
        );

        // Get parent object
        if (!array_key_exists($task->up, $row_cache['parent']))
        {
            $html = "&nbsp;";
            $ret['index_parent'] = $html;
            if ($task->up)
            {
                $parent = $task->get_parent();
                if ($parent->orgOpenpsaObtype == ORG_OPENPSA_OBTYPE_PROJECT)
                {
                    $parent_url = $data['prefix'] . "project/{$parent->guid}/";
                }
                else
                {
                    $parent_url = $data['prefix'] . "task/{$parent->guid}/";
                }
                $row_cache['index_parent'][$task->up] = $parent->title;
                $html = "<a href=\"{$parent_url}\">{$parent->title}</a>";
            }
            $row_cache['parent'][$task->up] = $html;
        }
        $ret['parent'] =& $row_cache['parent'][$task->up];
        $ret['index_parent'] =& $row_cache['index_parent'][$task->up];

        // Get agreement and customer (if applicable)
        if ($data['view_identifier'] != 'agreement')
        {
            if (!array_key_exists($task->customer, $row_cache['customer']))
            {
                $html = '';
                $row_cache['index_customer'][$task->customer] = $html;
                if ($task->customer)
                {
                    $customer = new org_openpsa_contacts_group_dba($task->customer);
                    $customer_url = "{$data['contacts_url']}group/{$customer->guid}/";
                    $html = "<a href='{$customer_url}' title='{$customer->official}'>{$customer->name}</a>";
                    $row_cache['index_customer'][$task->customer] = $customer->name;
                }
                $row_cache['customer'][$task->customer] = $html;
            }
            $ret['customer'] =& $row_cache['customer'][$task->customer];
            $ret['index_customer'] =& $row_cache['index_customer'][$task->customer];

            if (!array_key_exists($task->agreement, $row_cache['agreement']))
            {
                $html = "&nbsp;";
                $row_cache['index_agreement'][$task->agreement] = $html;
                if ($task->agreement)
                {
                    $agreement = new org_openpsa_sales_salesproject_deliverable_dba($task->agreement);
                    $salesproject = org_openpsa_sales_salesproject_dba::get_cached($agreement->salesproject);
                    $agreement_url = "{$data['sales_url']}salesproject/{$salesproject->guid}/";
                    $html = "<a href='{$agreement_url}'>{$agreement->title}</a>";
                    $row_cache['index_agreement'][$task->agreement] = $agreement->title;
                }
                $row_cache['agreement'][$task->agreement] = $html;
            }
            $ret['agreement'] =& $row_cache['agreement'][$task->agreement];
            $ret['index_agreement'] =& $row_cache['index_agreement'][$task->agreement];
        }

        return $ret;
    }

    /**
     * helper to get priorities from default-schema
     */
    function _get_priorities()
    {
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_task'));
        if(array_key_exists('priority' , $schemadb['default']->fields))
        {
            $this->_request_data['priority_array'] = $schemadb['default']->fields['priority']['type_config']['options'];
            $this->_request_data['priority_array'][0] = $this->_l10n->get("none");
            foreach($this->_request_data['priority_array'] as $key => $title)
            {
                $this->_request_data['priority_array'][$key] = $this->_l10n->get($title);
            }
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * helper to prepare non json output
     */
    private function _prepare_output()
    {
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $this->_request_data['contacts_url'] = $siteconfig->get_node_full_url('org.openpsa.contacts');
        $this->_request_data['sales_url'] = $siteconfig->get_node_full_url('org.openpsa.sales');
        $this->_request_data['prefix'] = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'project/new/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get("create project"),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-dir.png',
                MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_user_do('midgard:create', null, 'org_openpsa_projects_project'),
            )
        );

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'task/new/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get("create task"),
                MIDCOM_TOOLBAR_HELPTEXT => null,
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new_task.png',
                MIDCOM_TOOLBAR_ENABLED => $_MIDCOM->auth->can_user_do('midgard:create', null, 'org_openpsa_projects_task_dba'),
            )
        );
    }

    /**
     * Function to change data of tasks to array - because objects cause errors in json_encode ?
     */
    private function _change_tasks_for_json()
    {
        $task_array = array();
        $task_type = 0;
        foreach ($this->_request_data['tasks'] as $tasks)
        {
            $task_array[$task_type] = array();
            foreach ($tasks as $task)
            {
                $task_array[$task_type][] = array
                (
                    'title' => $task->title,
                    'guid' => $task->guid,
                    'priority' => $task->priority,
                    'priority_title' => $this->_request_data['priority_array'][$task->priority],
                    'planned_hours' => $task->plannedHours,
                    'invoiceable_hours' => $task->invoiceableHours,
                    'approved_hours' => $task->approvedHours,
                    'reported_hours' => $task->reportedHours,
                    'icon_url' => MIDCOM_STATIC_URL . "/stock-icons/16x16/" . $task->get_icon(),
                    'start' => date("d.m.Y" , $task->start),
                    'end' => date("d.m.Y" , $task->end),
                );
            }
            $task_type++;
        }
        $this->_request_data['tasks'] = $task_array;
    }
}
?>