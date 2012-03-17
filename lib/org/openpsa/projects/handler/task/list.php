<?php
/**
 * @package org.openpsa.projects
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Task list handler
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_handler_task_list extends midcom_baseclasses_components_handler
implements org_openpsa_widgets_grid_provider_client
{
    private $_status_order = array
    (
        'proposed' => 0,
        'current' => 1,
        'pending_accept' => 2,
        'pending_approve' => 3,
        'declined' => 4,
        'completed' => 5,
        'closed' => 6
    );

    /**
     * Grid controller
     *
     * @var org_openpsa_widgets_grid_provider
     */
    private $_provider;

    /**
     * Grid QB
     *
     * @var midcom_core_querybuilder
     */
    private $_qb;

    public function _on_initialize()
    {
        org_openpsa_widgets_contact::add_head_elements();
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user();

        if (isset($args[1]))
        {
            $this->_request_data['view_identifier'] = $args[1];
        }
        else
        {
            $this->_request_data['view_identifier'] = $handler_id;
        }
        //get possible priorities from schema
        $this->_get_priorities();
        $this->_qb = org_openpsa_projects_task_dba::new_query_builder();
        $this->_qb->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_TASK);

        switch ($args[0])
        {
            //for json no style is needed
            case 'all':
                $this->_prepare_output();
                org_openpsa_widgets_grid::add_head_elements();
                $this->_provider = new org_openpsa_widgets_grid_provider($this, 'local');
                $this->_handler_list_all($args);
                break;

            case 'project':
                $this->_prepare_output();
                org_openpsa_widgets_grid::add_head_elements();
                $this->_provider = new org_openpsa_widgets_grid_provider($this, 'local');
                $this->_handler_list_project($args);
                break;

            case 'json':
                $this->_provider = new org_openpsa_widgets_grid_provider($this, 'json');
                $this->_handler_list_project($args);
                //form of tasks has to be changed for json
                midcom::get()->skip_page_style = true;
                $this->_request_data['view'] = 'json';
                $siteconfig = org_openpsa_core_siteconfig::get_instance();
                $this->_request_data['contacts_url'] = $siteconfig->get_node_full_url('org.openpsa.contacts');
                break;

            default:
                throw new midcom_error('Invalid argument ' . $args[0]);
        }
    }

    private function _get_status_type(org_openpsa_projects_task_dba $task)
    {
        $type = 'closed';
        switch ($task->status)
        {
            case org_openpsa_projects_task_status_dba::PROPOSED:
                if ($task->manager != midcom_connection::get_user())
                {
                    $type = 'proposed';
                }
                else
                {
                    $type = 'pending_accept';
                }
                break;
            case org_openpsa_projects_task_status_dba::STARTED:
            case org_openpsa_projects_task_status_dba::REOPENED:
            case org_openpsa_projects_task_status_dba::ACCEPTED:
                $type = 'current';
                break;
            case org_openpsa_projects_task_status_dba::DECLINED:
                $type = 'declined';
                break;
            case org_openpsa_projects_task_status_dba::COMPLETED:
                if ($task->manager != midcom_connection::get_user())
                {
                    $type = 'completed';
                }
                else
                {
                    $type = 'pending_approve';
                }
                break;
        }

        return $type;
    }

    private function _render_workflow_controls(org_openpsa_projects_task_dba $task)
    {
        $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);

        $html = '';
        switch ($this->_get_status_type($task))
        {
            case 'proposed':
                if ($task->manager)
                {
                    $contact = org_openpsa_widgets_contact::get($task->manager);
                    $html .= sprintf($this->_l10n->get("from %s"), $contact->show_inline());
                }
                $task->get_members();
                if (   $task->can_do('midgard:update')
                    && isset($task->resources[midcom_connection::get_user()]))
                {
                    $html .= '<form method="post" action="' . $prefix . 'workflow/' . $task->guid . '/">';
                    //TODO: If we need all resources to accept task hide tools when we have accepted and replace with "pending acceptance from..."
                    $html .= '<ul class="area_toolbar">';
                    $html .= '<li><input type="submit" name="org_openpsa_projects_workflow_action[accept]" class="yes" value="' . $this->_l10n->get('accept') . '" /></li>';
                    $html .= '<li><input type="submit" name="org_openpsa_projects_workflow_action[decline]" class="no" value="' . $this->_l10n->get('decline') . '" /></li>';
                    $html .= "</ul></form>";
                }
                break;

            case 'pending_accept':
                // FIXME: List resources instead
                $task->get_members();
                if ( count($task->resources) > 0)
                {
                    $resources_string = '';
                    foreach ($task->resources as $id => $boolean)
                    {
                        $contact = org_openpsa_widgets_contact::get($id);
                        $resources_string .= ' ' . $contact->show_inline();
                    }
                    $html .= sprintf($this->_l10n->get("proposed to %s"), $resources_string);
                }
                break;

            case 'pending_approve':
                //PONDER: Check ACL instead?
                if (midcom_connection::get_user() == $task->manager)
                {
                    $html .= '<form method="post" action="' . $prefix . 'workflow/' . $task->guid . '">';
                    $html .= '  <ul class="area_toolbar">';
                    $html .= '<li><input type="submit" name="org_openpsa_projects_workflow_action[approve]" class="yes" value="' . $this->_l10n->get('approve') . '" /></li>';
                    //PONDER: This is kind of redundant  when one can just remove the checkbox -->
                    $html .=  '<li><input type="submit" name="org_openpsa_projects_workflow_action[reject]" class="no" value="' . $this->_l10n->get('dont approve') . '" /></li>';
                    $html .= "</ul></form>";
                }
                else if ($task->manager)
                {
                    $contact = org_openpsa_widgets_contact::get($task->manager);
                    $html .= sprintf($this->_l10n->get("pending approval by %s"), $contact->show_inline());
                }
                break;

            case 'declined':
                $task->get_members();
                if ( count($task->resources) > 0)
                {
                    $resources_string = '';
                    foreach ($task->resources as $id => $boolean)
                    {
                        $contact = org_openpsa_widgets_contact::get($id);
                        $resources_string .= ' ' . $contact->show_inline();
                    }
                    $html .= sprintf($this->_l10n->get("declined by %s"), $resources_string);
                }
                break;

            case 'completed':
                if ($task->manager)
                {
                    $contact = org_openpsa_widgets_contact::get($task->manager);

                    $html .= sprintf($this->_l10n->get("pending approval by %s"), $contact->show_inline());
                }

                break;
        }
        return $html;
    }

    public function _handler_list_user($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user();

        $this->_request_data['view'] = 'grid';
        $this->_request_data['view_identifier'] = 'my_tasks';
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $this->_request_data['contacts_url'] = $siteconfig->get_node_full_url('org.openpsa.contacts');

        //get possible priorities from schema
        $this->_get_priorities();
        $this->_provider = new org_openpsa_widgets_grid_provider($this, 'local');

        $resource_statuses = array
        (
            org_openpsa_projects_task_status_dba::PROPOSED,
            org_openpsa_projects_task_status_dba::ACCEPTED,
            org_openpsa_projects_task_status_dba::STARTED,
            org_openpsa_projects_task_status_dba::REOPENED,
            org_openpsa_projects_task_status_dba::COMPLETED
        );

        $task_statuses = array
        (
            org_openpsa_projects_task_status_dba::PROPOSED,
            org_openpsa_projects_task_status_dba::DECLINED,
            org_openpsa_projects_task_status_dba::COMPLETED,
            org_openpsa_projects_task_status_dba::ONHOLD
        );

        $mc = org_openpsa_projects_task_resource_dba::new_collector('person', midcom_connection::get_user());
        $mc->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_PROJECTRESOURCE);
        $mc->add_constraint('task.orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_TASK);
        $mc->add_constraint('task.status', 'IN', $resource_statuses);

        $resource_tasks = $mc->get_values('task');

        $this->_qb = org_openpsa_projects_task_dba::new_query_builder();
        $this->_qb->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_TASK);

        $this->_qb->begin_group('OR');
            if (!empty($resource_tasks))
            {
                //Get active tasks where user is a resource
                $this->_qb->add_constraint('id', 'IN', $resource_tasks);
            }
            //Get relevant tasks where user is manager
            $this->_qb->begin_group('AND');
                $this->_qb->add_constraint('manager', '=', midcom_connection::get_user());
                $this->_qb->add_constraint('status', 'IN', $task_statuses);
            $this->_qb->end_group();
        $this->_qb->end_group();

        org_openpsa_widgets_grid::add_head_elements();
    }

    public function get_qb($field = null, $direction = 'ASC')
    {
        if (!is_null($field))
        {
            $field = str_replace('index_', '', $field);
            $this->_qb->add_order($field, $direction);
        }
        $this->_qb->add_order('priority', 'ASC');
        $this->_qb->add_order('customer');
        $this->_qb->add_order('project');
        $this->_qb->add_order('title');
        return $this->_qb;
    }

    public function get_row(midcom_core_dbaobject $task)
    {
        $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
        $task_url = $prefix . "task/{$task->guid}/";
        $celldata = $this->get_table_row_data($task, $this->_request_data);
        $manager_card = org_openpsa_widgets_contact::get($task->manager);
        $entry = array();

        $entry['id'] = $task->id;
        $entry['index_task'] = $task->title;
        $entry['task'] = '<a href="' . $task_url . '"><img class="status-icon" src="' . MIDCOM_STATIC_URL . '/stock-icons/16x16/' . $task->get_icon() . '" /> ' . $task->title . '</a>';
        if (   $this->_request_data['view_identifier'] == 'my_tasks'
       	    || $this->_request_data['view_identifier'] == 'project_tasks')
        {
            $entry['status_control'] = org_openpsa_projects_workflow::render_status_control($task);
            $status_type = $this->_get_status_type($task);
            $entry['index_status'] = $this->_status_order[$status_type];
            $entry['status'] = $this->_l10n->get($status_type . ' tasks');
            $controls = $this->_render_workflow_controls($task);
            if ($controls != '')
            {
                $entry['task'] = '<div class="title">' . $entry['task'] . '</div><div class="details">' . $controls . '</div>';
            }
        }
        $entry['index_project'] = $celldata['index_parent'];
        $entry['project'] = $celldata['parent'];

        $entry['index_priority'] = $task->priority;
        $entry['priority'] = $task->priority;
        if (   isset($this->_request_data['priority_array'])
            && array_key_exists($task->priority, $this->_request_data['priority_array']))
        {
            $entry['priority'] = '<span title="' . $this->_l10n->get($this->_request_['priority_array'][$task->priority]) . '">' . $task->priority . '</span>';
        }

        $entry['index_customer'] = $celldata['index_customer'];
        $entry['customer'] = $celldata['customer'];

        $entry['manager'] = $manager_card->show_inline();
        $entry['index_manager'] = preg_replace('/<span.*?class="uid".*?>.*?<\/span>/', '', $entry['manager']);
        $entry['index_manager'] = strip_tags($entry['index_manager']);

        $entry['start'] = strftime('%Y-%m-%d', $task->start);
        $entry['end'] = strftime('%Y-%m-%d', $task->end);

        if ($this->_request_data['view_identifier'] != 'project_tasks')
        {
            $entry['index_reported'] = $task->reportedHours;
            $entry['reported'] = round($task->reportedHours, 2);
            if ($task->plannedHours > 0)
            {
                $entry['reported'] .=  ' / ' . round($task->plannedHours, 2);
            }
        }
        else
        {
            $entry['planned_hours'] = $task->plannedHours;
            $entry['invoiceable_hours'] = $task->invoiceableHours;
            $entry['approved_hours'] = $task->approvedHours;
            $entry['reported_hours'] = $task->reportedHours;
        }
        return $entry;
    }

    private function _handler_list_project(&$args)
    {
        $this->_request_data['project'] = new org_openpsa_projects_project($args[1]);

        // Query tasks of a project
        $this->_request_data['view'] = 'grid';
        $this->_request_data['view_identifier'] = 'project_tasks';

        $this->_qb->add_constraint('project', '=', $this->_request_data['project']->id);

        //don't filter for json
        if ($args[0] != 'json')
        {
            $qf = new org_openpsa_core_queryfilter('org_openpsa_task_list');
            $qf->add_filter(new org_openpsa_core_filter('priority', '<=', $this->_request_data['priority_array']));
            $qf->apply_filters($this->_qb);
            $this->_request_data["qf"] = $qf;
        }
    }

    /**
     * List all tasks, optionally filtered by status
     */
    private function _handler_list_all($args)
    {
        // Default to open tasks list if none specified
        if (   !isset($args[1])
            || empty($args[1]))
        {
            $this->_request_data['view_identifier'] = 'open';
            $args[1] = 'open';
        }

        switch ($args[1])
        {
            case 'agreement':
                if (!$args[2])
                {
                    throw new midcom_error('Invalid arguments for agreement filter');
                }
                $agreement_id = (int) $args[2];
                $this->_request_data['agreement'] = $agreement_id;

                $deliverable = org_openpsa_sales_salesproject_deliverable_dba::get_cached($agreement_id);
                $title = sprintf($this->_l10n->get('tasks for agreement %s'), $deliverable->title);
                midcom::get('head')->set_pagetitle($title);
                $this->add_breadcrumb("", $title);

                $this->_qb->add_constraint('agreement', '=', $deliverable->id);
                $this->_provider->add_order('end', 'DESC');
                break;
            case 'all':
            case 'both':
                $args[1] = 'all';
                $this->_provider->add_order('end', 'DESC');
                break;
            case 'open':
                $this->set_active_leaf($this->_topic->id . ':tasks_open');
                $this->_qb->add_constraint('status', '<', org_openpsa_projects_task_status_dba::CLOSED);
                $this->_provider->add_order('end');
                break;
            case 'closed':
                $this->set_active_leaf($this->_topic->id . ':tasks_closed');
                $this->_qb->add_constraint('status', '=', org_openpsa_projects_task_status_dba::CLOSED);
                $this->_provider->add_order('end', 'DESC');
                break;
            case 'current':
                $this->_qb->add_constraint
                (
                    'status',
                    'IN',
                    array
                    (
                        org_openpsa_projects_task_status_dba::ACCEPTED,
                        org_openpsa_projects_task_status_dba::STARTED,
                        org_openpsa_projects_task_status_dba::REJECTED,
                        org_openpsa_projects_task_status_dba::REOPENED
                    )
                );
                $this->_provider->add_order('end');
                break;
            case 'invoiceable':
                $this->set_active_leaf($this->_topic->id . ':tasks_invoiceable');
                $this->_qb->add_constraint('invoiceableHours', '>', 0);
                $this->_provider->add_order('end');
                break;
            case 'invoiced':
                $this->set_active_leaf($this->_topic->id . ':tasks_invoiced');
                $this->_qb->add_constraint('invoicedHours', '>', 0);
                $this->_provider->add_order('end', 'DESC');
                break;
            default:
                throw new midcom_error("Filter {$args[1]} not recognized");
        }

        $qf = new org_openpsa_core_queryfilter('org_openpsa_task_list_' . $args[1]);
        $qf->add_filter(new org_openpsa_core_filter('priority', '<=', $this->_request_data['priority_array']));
        $date_filter = new org_openpsa_core_filter('timeframe');
        $date_filter->set('mode', 'timeframe');
        $date_filter->set('helptext', $this->_l10n->get("timeframe"));
        $date_filter->set('fieldname', array('start' => 'start', 'end' => 'end'));
        $qf->add_filter($date_filter);
        $qf->apply_filters($this->_qb);
        $this->_request_data["qf"] = $qf;

        $this->_request_data['table-heading'] = $args[1] . ' tasks';
        $this->_request_data['view'] = 'grid';
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_list($handler_id, array &$data)
    {
        $data['provider'] = $this->_provider;
        if ($data['view'] == 'json')
        {
            midcom_show_style('show-json-tasks');
        }
        else
        {
            if ($data['view_identifier'] == 'agreement')
            {
                midcom_show_style("show-task-grid-agreement");
            }
            else
            {
                if ($data['view_identifier'] != 'my_tasks')
                {
                    midcom_show_style("show-priority-filter");
                }
                midcom_show_style("show-task-grid");
            }
        }
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_list_user($handler_id, array &$data)
    {
        $this->_show_list($handler_id, $data);
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
        );

        // Get parent object
        if (!array_key_exists($task->project, $row_cache['parent']))
        {
            $html = "&nbsp;";
            $row_cache['index_parent'][$task->project] = '';

            if ($parent = $task->get_parent())
            {
                $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
                if (is_a($parent, 'org_openpsa_projects_project'))
                {
                    $parent_url = $prefix . "project/{$parent->guid}/";
                }
                else
                {
                    $parent_url = $prefix . "task/{$parent->guid}/";
                }
                $row_cache['index_parent'][$task->project] = $parent->title;
                $html = "<a href=\"{$parent_url}\">{$parent->title}</a>";
            }
            $row_cache['parent'][$task->project] = $html;
        }
        $ret['parent'] =& $row_cache['parent'][$task->project];
        $ret['index_parent'] =& $row_cache['index_parent'][$task->project];

        // Get agreement and customer (if applicable)
        if ($this->_request_data['view_identifier'] != 'agreement')
        {
            if (!array_key_exists($task->customer, $row_cache['customer']))
            {
                try
                {
                    $customer = new org_openpsa_contacts_group_dba($task->customer);
                    $customer_url = "{$this->_request_data['contacts_url']}group/{$customer->guid}/";
                    $html = "<a href='{$customer_url}' title='{$customer->official}'>{$customer->name}</a>";
                    $row_cache['index_customer'][$task->customer] = $customer->name;
                }
                catch (midcom_error $e)
                {
                    $html = '';
                    $row_cache['index_customer'][$task->customer] = $html;
                }
                $row_cache['customer'][$task->customer] = $html;
            }
            $ret['customer'] =& $row_cache['customer'][$task->customer];
            $ret['index_customer'] =& $row_cache['index_customer'][$task->customer];
        }

        return $ret;
    }

    /**
     * Helper to get priorities from default-schema
     */
    private function _get_priorities()
    {
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_task'));
        if (array_key_exists('priority', $schemadb['default']->fields))
        {
            $this->_request_data['priority_array'] = $schemadb['default']->fields['priority']['type_config']['options'];
            $this->_request_data['priority_array'][0] = $this->_l10n->get("none");
            foreach ($this->_request_data['priority_array'] as $key => $title)
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
     * Helper to prepare non json output
     */
    private function _prepare_output()
    {
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $this->_request_data['contacts_url'] = $siteconfig->get_node_full_url('org.openpsa.contacts');
        $this->_request_data['sales_url'] = $siteconfig->get_node_full_url('org.openpsa.sales');
        $this->_request_data['prefix'] = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);

        if ($this->_request_data['view_identifier'] == 'agreement')
        {
            return;
        }

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'project/new/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get("create project"),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-dir.png',
                MIDCOM_TOOLBAR_ENABLED => midcom::get('auth')->can_user_do('midgard:create', null, 'org_openpsa_projects_project'),
            )
        );

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'task/new/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get("create task"),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new_task.png',
                MIDCOM_TOOLBAR_ENABLED => midcom::get('auth')->can_user_do('midgard:create', null, 'org_openpsa_projects_task_dba'),
            )
        );
    }
}
?>