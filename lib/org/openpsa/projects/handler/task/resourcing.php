<?php
/**
 * @package org.openpsa.projects
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Projects task resourcing handler
 *
 * @package org.openpsa.projects
 */
class org_openpsa_projects_handler_task_resourcing extends midcom_baseclasses_components_handler
{
    /**
     * The task to operate on
     *
     * @var org_openpsa_projects_task_dba
     */
    private $_task = null;

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data($handler_id)
    {
        $this->_request_data['task'] = $this->_task;

        if ($this->_task->can_do('midgard:update')) {
            $workflow = $this->get_workflow('datamanager');
            $this->_view_toolbar->add_item($workflow->get_button($this->router->generate('task_edit', ['guid' => $this->_task->guid]), [
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            ]));
        }
    }

    /**
     * Maps the content topic from the request data to local member variables.
     */
    public function _on_initialize()
    {
        org_openpsa_widgets_contact::add_head_elements();

        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . "/org.openpsa.projects/projectbroker.js");
    }

    /**
     * Display possible available resources
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_resourcing($handler_id, array $args, array &$data)
    {
        $this->_task = new org_openpsa_projects_task_dba($args[0]);
        $this->_task->require_do('midgard:create');

        if (   array_key_exists('org_openpsa_projects_prospects', $_POST)
            && $_POST['save']) {
            $qb = org_openpsa_projects_task_resource_dba::new_query_builder();
            $qb->add_constraint('guid', 'IN', array_keys($_POST['org_openpsa_projects_prospects']));
            foreach ($qb->execute() as $prospect) {
                $slots = $_POST['org_openpsa_projects_prospects'][$prospect->guid];
                $update_prospect = false;
                foreach ($slots as $slotdata) {
                    if (empty($slotdata['used'])) {
                        // Slot not selected, skip
                        continue;
                    }
                    $prospect->orgOpenpsaObtype = org_openpsa_projects_task_resource_dba::RESOURCE;
                    $update_prospect = true;
                    // Create event from slot
                    $event = new org_openpsa_calendar_event_dba();
                    $event->start = $slotdata['start'];
                    $event->end = $slotdata['end'];
                    $event->search_relatedtos = false;
                    $event->title = sprintf($this->_l10n->get('work for task %s'), $this->_task->title);
                    if (!$event->create()) {
                        // TODO: error reporting
                        continue;
                    }
                    $participant = new org_openpsa_calendar_event_member_dba();
                    $participant->uid = $prospect->person;
                    $participant->eid = $event->id;
                    $participant->create();
                    // create relatedto
                    org_openpsa_relatedto_plugin::create($event, 'org.openpsa.calendar', $this->_task, 'org.openpsa.projects');
                }
                if (   $update_prospect
                    && !$prospect->update()) {
                    debug_add('Failed to update prospect: ' . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                }
            }
            return new midcom_response_relocate($this->router->generate('task_view', ['guid' => $this->_task->guid]));
        }
        if (!empty($_POST['cancel'])) {
            return new midcom_response_relocate($this->router->generate('task_view', ['guid' => $this->_task->guid]));
        }

        $this->_prepare_request_data($handler_id);
        midcom::get()->head->set_pagetitle($this->_task->title);
        $this->bind_view_to_object($this->_task);

        org_openpsa_projects_viewer::add_breadcrumb_path($data['task'], $this);
        $this->add_breadcrumb($this->router->generate('task_resourcing', ['guid' => $this->_task->guid]), $this->_l10n->get('resourcing'));

        return $this->show('show-task-resourcing');
    }

    /**
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_list_prospects(array $args, array &$data)
    {
        $this->_task = new org_openpsa_projects_task_dba($args[0]);
        $this->_task->require_do('midgard:create');

        $qb = org_openpsa_projects_task_resource_dba::new_query_builder();
        $qb->add_constraint('task', '=', $this->_task->id);
        $qb->add_constraint('orgOpenpsaObtype', '=', org_openpsa_projects_task_resource_dba::RESOURCE);
        $data['prospects'] = $qb->execute();

        midcom::get()->skip_page_style = true;
        midcom::get()->header("Content-type: text/xml; charset=UTF-8");

        return $this->show('show-prospects-xml');
    }

    /**
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_prospect_slots(array $args, array &$data)
    {
        $data['prospect'] = new org_openpsa_projects_task_resource_dba($args[0]);
        $data['person'] = new org_openpsa_contacts_person_dba($data['prospect']->person);
        $this->_task = new org_openpsa_projects_task_dba($data['prospect']->task);
        $this->_task->require_do('midgard:create');

        $minimum_time_slot = $this->_task->get_parameter('org.openpsa.projects.projectbroker', 'minimum_slot');
        if (empty($minimum_time_slot)) {
            // Default to 15 minutes for minimum time here
            $minimum_time_slot = 0.25;
        }
        $data['slots'] = org_openpsa_calendar_event_member_dba::find_free_times(
            ($minimum_time_slot * 60),
            $data['person'],
            $this->_task->start,
            $this->_task->end);

        midcom::get()->skip_page_style = true;

        return $this->show('show-prospect');
    }
}
