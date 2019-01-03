<?php
/**
 * @package org.openpsa.projects
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\schemadb;
use midcom\grid\provider\client;
use midcom\grid\provider;
use midcom\grid\grid;

/**
 * Task list handler
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_handler_task_list extends midcom_baseclasses_components_handler
implements client
{
    private $status_order = [
        'proposed' => 0,
        'current' => 1,
        'pending_accept' => 2,
        'pending_approve' => 3,
        'declined' => 4,
        'completed' => 5,
        'closed' => 6
    ];

    /**
     * Grid controller
     *
     * @var provider
     */
    protected $provider;

    /**
     * Grid QB
     *
     * @var midcom_core_querybuilder
     */
    protected $qb;

    /**
     * Whether to render the status controls
     *
     * @var boolean
     */
    protected $show_status_controls = false;

    /**
     * Whether to render the customer
     *
     * @var boolean
     */
    protected $show_customer = true;

    /**
     * Do all the tasks belong to the same project
     *
     * @var boolean
     */
    protected $is_single_project = false;

    public function _on_initialize()
    {
        midcom::get()->auth->require_valid_user();
        org_openpsa_widgets_contact::add_head_elements();
        $this->get_priorities();
    }

    /**
     * Get priorities from default schema
     */
    private function get_priorities()
    {
        $schemadb = schemadb::from_path($this->_config->get('schemadb_task'));
        if ($schemadb->get('default')->has_field('priority')) {
            $this->_request_data['priority_array'] = $schemadb->get('default')->get_field('priority')['type_config']['options'];
            $this->_request_data['priority_array'][0] = $this->_l10n->get("none");
            foreach ($this->_request_data['priority_array'] as &$title) {
                $title = $this->_l10n->get($title);
            }
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
        $this->prepare_request_data($args[0]);
        $this->prepare_toolbar();

        $this->qb = org_openpsa_projects_task_dba::new_query_builder();
        switch ($args[0]) {
            case 'open':
                $this->qb->add_constraint('status', '<', org_openpsa_projects_task_status_dba::CLOSED);
                $this->provider->add_order('end');
                break;
            case 'closed':
                $this->qb->add_constraint('status', '=', org_openpsa_projects_task_status_dba::CLOSED);
                $this->provider->add_order('end', 'DESC');
                break;
            case 'current':
                $this->qb->add_constraint('status', 'IN', [
                    org_openpsa_projects_task_status_dba::ACCEPTED,
                    org_openpsa_projects_task_status_dba::STARTED,
                    org_openpsa_projects_task_status_dba::REJECTED,
                    org_openpsa_projects_task_status_dba::REOPENED
                ]);
                $this->provider->add_order('end');
                break;
            case 'invoiceable':
                $this->qb->add_constraint('invoiceableHours', '>', 0);
                $this->provider->add_order('end');
                break;
            case 'invoiced':
                $this->qb->add_constraint('invoicedHours', '>', 0);
                $this->provider->add_order('end', 'DESC');
                break;
        }
        $this->set_active_leaf($this->_topic->id . ':tasks_' . $args[0]);
        $this->add_filters($args[0]);
        $this->_request_data['table-heading'] = $args[0] . ' tasks';
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_list($handler_id, array &$data)
    {
        midcom_show_style('show-priority-filter');
        midcom_show_style('show-task-grid');
    }

    private function get_status_type(org_openpsa_projects_task_dba $task)
    {
        $type = 'closed';
        $is_manager = $task->manager == midcom_connection::get_user();
        switch ($task->status) {
            case org_openpsa_projects_task_status_dba::PROPOSED:
                $type = ($is_manager) ? 'pending_accept' : 'proposed';
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
                $type = ($is_manager) ? 'pending_approve' : 'completed';
                break;
        }

        return $type;
    }

    private function render_workflow_controls(org_openpsa_projects_task_dba $task)
    {
        switch ($this->get_status_type($task)) {
            case 'proposed':
                $html = $this->render_status($task->manager, "from %s");
                $task->get_members();
                if (   $task->can_do('midgard:update')
                    && isset($task->resources[midcom_connection::get_user()])) {
                    $html .= '<form method="post" action="' . $this->router->generate('workflow', ['guid' => $task->guid]) . '">';
                    //TODO: If we need all resources to accept task hide tools when we have accepted and replace with "pending acceptance from..."
                    $html .= '<ul class="area_toolbar">';
                    $html .= '<li><button type="submit" name="org_openpsa_projects_workflow_action[accept]" class="yes"><i class="fa fa-check"></i> ' . $this->_l10n->get('accept') . '</button></li>';
                    $html .= '<li><button type="submit" name="org_openpsa_projects_workflow_action[decline]" class="no"><i class="fa fa-ban"></i> ' . $this->_l10n->get('decline') . '</button></li>';
                    $html .= "</ul></form>";
                }
                return $html;

            case 'pending_accept':
                $task->get_members();
                return $this->render_status($task->resources, "proposed to %s");

            case 'pending_approve':
                //PONDER: Check ACL instead?
                if (midcom_connection::get_user() == $task->manager) {
                    $html = '<form method="post" action="' . $this->router->generate('workflow', ['guid' => $task->guid]) . '">';
                    $html .= '<ul class="area_toolbar">';
                    $html .= '<li><button type="submit" name="org_openpsa_projects_workflow_action[approve]" class="yes"><i class="fa fa-check"></i> ' . $this->_l10n->get('approve') . '</button></li>';
                    //PONDER: This is kind of redundant  when one can just remove the checkbox -->
                    $html .= '<li><button type="submit" name="org_openpsa_projects_workflow_action[reject]" class="no"><i class="fa fa-ban"></i> ' . $this->_l10n->get('dont approve') . '</button></li>';
                    $html .= "</ul></form>";
                    return $html;
                }
                return $this->render_status($task->manager, "pending approval by %s");

            case 'declined':
                $task->get_members();
                return $this->render_status($task->resources, "declined by %s");

            case 'completed':
                return $this->render_status($task->manager, "pending approval by %s");
        }
        return '';
    }

    private function render_status($ids, $message)
    {
        if (empty($ids)) {
            return '';
        }
        if (is_scalar($ids)) {
            $ids = [$ids => true];
        }
        $person_string = '';
        foreach (array_keys($ids) as $id) {
            $contact = org_openpsa_widgets_contact::get($id);
            $person_string .= ' ' . $contact->show_inline();
        }
        return sprintf($this->_l10n->get($message), $person_string);
    }

    public function get_qb($field = null, $direction = 'ASC', array $search = [])
    {
        if (!is_null($field)) {
            $this->qb->add_order($field, $direction);
        }
        $this->qb->add_order('priority', 'ASC');
        $this->qb->add_order('customer');
        $this->qb->add_order('project');
        $this->qb->add_order('title');
        return $this->qb;
    }

    public function get_row(midcom_core_dbaobject $task)
    {
        $task_url = $this->router->generate('task_view', ['guid' => $task->guid]);
        $manager_card = org_openpsa_widgets_contact::get($task->manager);

        $entry = $this->get_table_row_data($task);
        $entry['id'] = $task->id;
        $entry['index_task'] = $task->title;
        $entry['task'] = '<a href="' . $task_url . '" class="workflow-status ' . $task->status_type . '" title="' . $this->_l10n->get($task->status_type) . '">' . $task->title . '</a>';
        if ($this->show_status_controls) {
            $entry['status_control'] = org_openpsa_projects_workflow::render_status_control($task);
            $status_type = $this->get_status_type($task);
            $entry['index_status'] = $this->status_order[$status_type];
            $entry['status'] = $this->_l10n->get($status_type . ' tasks');
            if ($controls = $this->render_workflow_controls($task)) {
                $entry['task'] = '<div class="title">' . $entry['task'] . '</div><div class="details">' . $controls . '</div>';
            }
        }

        $entry['index_priority'] = $task->priority;
        $entry['priority'] = $task->priority;
        if (!empty($this->_request_data['priority_array'][$task->priority])) {
            $entry['priority'] = '<span title="' . $this->_l10n->get($this->_request_data['priority_array'][$task->priority]) . '">' . $task->priority . '</span>';
        }

        $entry['manager'] = $manager_card->show_inline();
        $entry['index_manager'] = preg_replace('/<span.*?class="uid".*?>.*?<\/span>/', '', $entry['manager']);
        $entry['index_manager'] = strip_tags($entry['index_manager']);

        $entry['start'] = strftime('%Y-%m-%d', $task->start);
        $entry['end'] = strftime('%Y-%m-%d', $task->end);

        $entry['planned_hours'] = $task->plannedHours;
        if ($this->is_single_project) {
            $entry['invoiced_hours'] = $task->invoicedHours;
        }
        $entry['approved_hours'] = $task->approvedHours;
        $entry['reported_hours'] = $task->reportedHours;

        return $entry;
    }

    protected function add_filters($identifier)
    {
        $qf = new org_openpsa_core_queryfilter('org_openpsa_task_list_' . $identifier);
        $p_filter = new org_openpsa_core_filter_select('priority', '<=', $this->_request_data['priority_array']);
        $p_filter->set_label($this->_l10n->get('only tasks with priority'));
        $qf->add_filter($p_filter);
        $date_filter = new org_openpsa_core_filter_timeframe('timeframe', 'start', 'end');
        $date_filter->set_label($this->_l10n->get("timeframe"));
        $qf->add_filter($date_filter);
        $qf->apply_filters($this->qb);
        $this->_request_data["qf"] = $qf;
    }

    /**
     * Get the relevant data for cells in table view
     */
    private function get_table_row_data($task)
    {
        $ret = [
            'project' => '&nbsp;',
            'index_project' => '',
        ];

        try {
            $project = org_openpsa_projects_project::get_cached($task->project);
            $url = $this->router->generate('project', ['guid' => $project->guid]);
            $ret['project'] = '<a href="' . $url . '">' . $project->title . '</a>';
            $ret['index_project'] = $project->title;
        } catch (midcom_error $e) {
            $e->log();
        }

        if ($this->show_customer) {
            try {
                $customer = org_openpsa_contacts_group_dba::get_cached($task->customer);
                $customer_url = "{$this->_request_data['contacts_url']}group/{$customer->guid}/";
                $ret['customer'] = "<a href='{$customer_url}' title='{$customer->official}'>{$customer->get_label()}</a>";
                $ret['index_customer'] = $customer->name;
            } catch (midcom_error $e) {
                $ret['customer'] = '';
                $ret['index_customer'] = '';
            }
        }

        return $ret;
    }

    protected function prepare_toolbar()
    {
        $workflow = $this->get_workflow('datamanager');

        if (midcom::get()->auth->can_user_do('midgard:create', null, org_openpsa_projects_project::class)) {
            $this->_view_toolbar->add_item($workflow->get_button($this->router->generate('project-new'), [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get("create project"),
                MIDCOM_TOOLBAR_GLYPHICON => 'tasks',
            ]));
        }
        if (midcom::get()->auth->can_user_do('midgard:create', null, org_openpsa_projects_task_dba::class)) {
            $this->_view_toolbar->add_item($workflow->get_button($this->router->generate('task-new'), [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get("create task"),
                MIDCOM_TOOLBAR_GLYPHICON => 'calendar-check-o',
            ]));
        }
    }

    /**
     * @param string $identifier
     * @param string $datatype
     */
    protected function prepare_request_data($identifier, $datatype = 'local')
    {
        $this->_request_data['view_identifier'] = $identifier;
        $this->_request_data['show_status_controls'] = $this->show_status_controls;
        $this->_request_data['show_customer'] = $this->show_customer;
        $this->_request_data['is_single_project'] = $this->is_single_project;

        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $this->_request_data['contacts_url'] = $siteconfig->get_node_full_url('org.openpsa.contacts');
        $this->_request_data['sales_url'] = $siteconfig->get_node_full_url('org.openpsa.sales');

        $this->provider = new provider($this, 'local');
        $this->_request_data['provider'] = $this->provider;

        grid::add_head_elements();
    }
}
