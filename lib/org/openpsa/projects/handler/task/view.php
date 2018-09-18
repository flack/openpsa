<?php
/**
 * @package org.openpsa.projects
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;

/**
 * Projects task handler
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_handler_task_view extends midcom_baseclasses_components_handler
{
    /**
     * @var org_openpsa_projects_task_dba
     */
    private $task;

    /**
     * Generates an object view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_read($handler_id, array $args, array &$data)
    {
        $this->task = new org_openpsa_projects_task_dba($args[0]);

        $data['object'] = $this->task;
        $data['datamanager'] = datamanager::from_schemadb($this->_config->get('schemadb_task'))
            ->set_storage($this->task);
        $data['object_view'] = $data['datamanager']->get_content_html();

        $this->populate_toolbar();
        midcom::get()->head->set_pagetitle($this->task->get_label());
        org_openpsa_projects_viewer::add_breadcrumb_path($this->task, $this);
        midcom::get()->metadata->set_request_metadata($this->task->metadata->revised, $this->task->guid);
        $this->bind_view_to_object($this->task, $data['datamanager']->get_schema()->get_name());

        org_openpsa_widgets_ui::enable_ui_tab();
        org_openpsa_widgets_contact::add_head_elements();
        $data['calendar_node'] = midcom_helper_misc::find_node_by_component('org.openpsa.calendar');

        $qb = org_openpsa_projects_task_dba::new_query_builder();
        $qb->add_constraint('up', '=', $this->task->id);
        $data['has_subtasks'] = $qb->count() > 0;
        $data['task_bookings'] = $this->list_bookings();

        return $this->show('show-task');
    }

    /**
     * Special helper for adding the supported operations from read into the toolbar.
     */
    private function populate_toolbar()
    {
        if (!$this->task->can_do('midgard:update')) {
            return;
        }
        $buttons = [];
        $workflow = $this->get_workflow('datamanager');
        $buttons[] = $workflow->get_button($this->router->generate('task_edit', ['guid' => $this->task->guid]), [
            MIDCOM_TOOLBAR_ACCESSKEY => 'e',
        ]);

        if (   $this->task->reportedHours == 0
            && $this->task->can_do('midgard:delete')) {
            $delete_workflow = $this->get_workflow('delete', ['object' => $this->task]);
            $buttons[] = $delete_workflow->get_button($this->router->generate('task_delete', ['guid' => $this->task->guid]));
        }

        if ($this->task->can_do('midgard:create')) {
            $buttons[] = $workflow->get_button($this->router->generate('task-new-2', [
                'guid' => $this->task->guid,
                'type' => 'task'
            ]), [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get("create task"),
                MIDCOM_TOOLBAR_GLYPHICON => 'calendar-check-o',
            ]);
        }

        if ($this->task->status == org_openpsa_projects_task_status_dba::CLOSED) {
            $buttons[] = [
                MIDCOM_TOOLBAR_URL => $this->router->generate('workflow', ['guid' => $this->task->guid]),
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('reopen'),
                MIDCOM_TOOLBAR_GLYPHICON => 'refresh',
                MIDCOM_TOOLBAR_POST => true,
                MIDCOM_TOOLBAR_POST_HIDDENARGS => [
                    'org_openpsa_projects_workflow_action[reopen]' => 'dummy',
                    'org_openpsa_projects_workflow_action_redirect' => $this->router->generate('task_view', ['guid' => $this->task->guid])
                ],
            ];
        } elseif ($this->task->status < org_openpsa_projects_task_status_dba::COMPLETED) {
            $buttons[] = [
                MIDCOM_TOOLBAR_URL => $this->router->generate('workflow', ['guid' => $this->task->guid]),
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('mark completed'),
                MIDCOM_TOOLBAR_GLYPHICON => 'check',
                MIDCOM_TOOLBAR_POST => true,
                MIDCOM_TOOLBAR_POST_HIDDENARGS => [
                    'org_openpsa_projects_workflow_action[complete]' => 'dummy',
                    'org_openpsa_projects_workflow_action_redirect' => $this->router->generate('task_view', ['guid' => $this->task->guid])
                ],
            ];
        }

        if ($agreement = $this->task->get_agreement()) {
            try {
                $agreement = org_openpsa_sales_salesproject_deliverable_dba::get_cached($agreement);
                $siteconfig = org_openpsa_core_siteconfig::get_instance();
                if ($sales_url = $siteconfig->get_node_full_url('org.openpsa.sales')) {
                    $buttons[] = [
                        MIDCOM_TOOLBAR_URL => "{$sales_url}deliverable/{$agreement->guid}/",
                        MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('agreement'),
                        MIDCOM_TOOLBAR_GLYPHICON => 'money',
                    ];
                }
            } catch (midcom_error $e) {
            }
        }

        $this->_view_toolbar->add_items($buttons);
    }

    private function list_bookings()
    {
        $task_booked_time = 0;
        $task_booked_percentage = 100;

        $bookings = [
            'confirmed' => [],
            'suspected' => [],
        ];
        $mc = new org_openpsa_relatedto_collector($this->task->guid, org_openpsa_calendar_event_dba::class);
        $mc->add_object_order('start', 'ASC');
        $events = $mc->get_related_objects_grouped_by('status');

        foreach ($events as $status => $list) {
            if ($status == org_openpsa_relatedto_dba::CONFIRMED) {
                $bookings['confirmed'] = $list;
            } else {
                $bookings['suspected'] = $list;
            }
        }
        foreach ($bookings['confirmed'] as $booking) {
            $task_booked_time += ($booking->end - $booking->start) / 3600;
        }

        $task_booked_time = round($task_booked_time);

        if ($this->task->plannedHours != 0) {
            $task_booked_percentage = round(100 / $this->task->plannedHours * $task_booked_time);
        }

        $this->_request_data['task_booked_percentage'] = $task_booked_percentage;
        $this->_request_data['task_booked_time'] = $task_booked_time;

        return $bookings;
    }
}
