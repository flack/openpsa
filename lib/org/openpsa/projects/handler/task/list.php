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
        midcom::get()->auth->require_valid_user();
        org_openpsa_widgets_contact::add_head_elements();
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
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

        switch ($args[0])
        {
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
                $task->get_members();
                if ( count($task->resources) > 0)
                {
                    $resources_string = '';
                    foreach (array_keys($task->resources) as $id)
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
                    foreach (array_keys($task->resources) as $id)
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
        $mc->add_constraint('orgOpenpsaObtype', '=', org_openpsa_projects_task_resource_dba::RESOURCE);
        $mc->add_constraint('task.orgOpenpsaObtype', '=', org_openpsa_projects_task_dba::OBTYPE);
        $mc->add_constraint('task.status', 'IN', $resource_statuses);

        $this->_qb = org_openpsa_projects_task_dba::new_query_builder();

        $this->_qb->begin_group('OR');
            //Get active tasks where user is a resource
            $this->_qb->add_constraint('id', 'IN', $mc->get_values('task'));
            //Get relevant tasks where user is manager
            $this->_qb->begin_group('AND');
                $this->_qb->add_constraint('manager', '=', midcom_connection::get_user());
                $this->_qb->add_constraint('status', 'IN', $task_statuses);
            $this->_qb->end_group();
        $this->_qb->end_group();

        org_openpsa_widgets_grid::add_head_elements();
    }

    public function get_qb($field = null, $direction = 'ASC', array $search = array())
    {
        if (!is_null($field))
        {
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
            if ($controls = $this->_render_workflow_controls($task))
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
            $entry['priority'] = '<span title="' . $this->_l10n->get($this->_request_data['priority_array'][$task->priority]) . '">' . $task->priority . '</span>';
        }

        if (   $this->_request_data['view_identifier'] != 'agreement'
            && $this->_request_data['view_identifier'] != 'project_tasks')
        {
            $entry['index_customer'] = $celldata['index_customer'];
            $entry['customer'] = $celldata['customer'];
        }

        $entry['manager'] = $manager_card->show_inline();
        $entry['index_manager'] = preg_replace('/<span.*?class="uid".*?>.*?<\/span>/', '', $entry['manager']);
        $entry['index_manager'] = strip_tags($entry['index_manager']);

        $entry['start'] = strftime('%Y-%m-%d', $task->start);
        $entry['end'] = strftime('%Y-%m-%d', $task->end);

        $entry['planned_hours'] = $task->plannedHours;
        if ($this->_request_data['view_identifier'] == 'project_tasks')
        {
            $entry['invoiced_hours'] = $task->invoicedHours;
        }
        $entry['approved_hours'] = $task->approvedHours;
        $entry['reported_hours'] = $task->reportedHours;

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
            $this->_add_filters('project');
        }
        else
        {
            $this->_qb->add_order('status');
            $this->_qb->add_order('end', 'DESC');
            $this->_qb->add_order('start');
        }
    }

    /**
     * List all tasks, optionally filtered by status
     */
    private function _handler_list_all($args)
    {
        // Default to open tasks list if none specified
        if (empty($args[1]))
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
                midcom::get()->head->set_pagetitle($title);
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
        $this->_add_filters($args[1]);
        $this->_request_data['table-heading'] = $args[1] . ' tasks';
        $this->_request_data['view'] = 'grid';
    }

    private function _add_filters($identifier)
    {
        $qf = new org_openpsa_core_queryfilter('org_openpsa_task_list_' . $identifier);
        $p_filter = new org_openpsa_core_filter_select('priority', '<=', $this->_request_data['priority_array']);
        $p_filter->set_label($this->_l10n->get('only tasks with priority'));
        $qf->add_filter($p_filter);
        $date_filter = new org_openpsa_core_filter_timeframe('timeframe', 'start', 'end');
        $date_filter->set_label($this->_l10n->get("timeframe"));
        $qf->add_filter($date_filter);
        $qf->apply_filters($this->_qb);
        $this->_request_data["qf"] = $qf;
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
            if (   $data['view_identifier'] != 'my_tasks'
                && $data['view_identifier'] != 'agreement')
            {
                midcom_show_style('show-priority-filter');
            }
            midcom_show_style('show-task-grid');
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
     * Get the relevant data for cells in table view
     */
    public function get_table_row_data($task, &$data)
    {
        $ret = array
        (
            'parent' => '&nbsp;',
            'index_parent' => '',
        );

        try
        {
            $project = org_openpsa_projects_project::get_cached($task->project);
            $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
            $ret['parent'] = '<a href="' . $prefix . 'project/' . $project->guid . '/">' . $project->title . '</a>';
            $ret['index_parent'] = $project->title;

        }
        catch (midcom_error $e)
        {
            $e->log();
        }

        if ($this->_request_data['view_identifier'] != 'agreement')
        {
            try
            {
                $customer = org_openpsa_contacts_group_dba::get_cached($task->customer);
                $customer_url = "{$this->_request_data['contacts_url']}group/{$customer->guid}/";
                $ret['customer'] = "<a href='{$customer_url}' title='{$customer->official}'>{$customer->get_label()}</a>";
                $ret['index_customer'] = $customer->name;
            }
            catch (midcom_error $e)
            {
                $ret['customer'] = '';
                $ret['index_customer'] = '';
            }
        }

        return $ret;
    }

    /**
     * Get priorities from default schema
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
        }
    }

    /**
     * Prepare non-JSON output
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
        $workflow = $this->get_workflow('datamanager2');

        if (midcom::get()->auth->can_user_do('midgard:create', null, 'org_openpsa_projects_project'))
        {
            $this->_view_toolbar->add_item($workflow->get_button('project/new/', array
            (
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get("create project"),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-dir.png',
            )));
        }
        if (midcom::get()->auth->can_user_do('midgard:create', null, 'org_openpsa_projects_task_dba'))
        {
            $this->_view_toolbar->add_item($workflow->get_button('task/new/', array
            (
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get("create task"),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new_task.png',
            )));
        }
    }
}
