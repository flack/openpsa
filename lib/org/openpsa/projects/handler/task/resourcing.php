<?php
/**
 * @package org.openpsa.projects
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Request;

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
    private $_task;

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
        midcom::get()->uimessages->add_head_elements();
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . "/org.openpsa.projects/projectbroker.js");
    }

    /**
     * Display possible available resources
     */
    public function _handler_resourcing(Request $request, string $handler_id, string $guid, array &$data)
    {
        $this->_task = new org_openpsa_projects_task_dba($guid);
        $this->_task->require_do('midgard:create');

        if ($request->request->has('cancel')) {
            return new midcom_response_relocate($this->router->generate('task_view', ['guid' => $guid]));
        }

        if (   $request->request->has('save')
            && $prospects = $request->request->get('org_openpsa_projects_prospects')) {
            $qb = org_openpsa_projects_task_resource_dba::new_query_builder();
            $qb->add_constraint('guid', 'IN', array_keys($prospects));
            foreach ($qb->execute() as $prospect) {
                $slots = $prospects[$prospect->guid];
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
            return new midcom_response_relocate($this->router->generate('task_view', ['guid' => $guid]));
        }

        $this->_prepare_request_data($handler_id);
        midcom::get()->head->set_pagetitle($this->_task->title);
        $this->bind_view_to_object($this->_task);

        org_openpsa_projects_viewer::add_breadcrumb_path($data['task'], $this);
        $this->add_breadcrumb($this->router->generate('task_resourcing', ['guid' => $guid]), $this->_l10n->get('resourcing'));

        return $this->show('show-task-resourcing');
    }

    public function _handler_list_prospects(string $guid, array &$data)
    {
        $this->_task = new org_openpsa_projects_task_dba($guid);
        $this->_task->require_do('midgard:create');

        $qb = org_openpsa_projects_task_resource_dba::new_query_builder();
        $qb->add_constraint('task', '=', $this->_task->id);
        $qb->add_constraint('orgOpenpsaObtype', '=', org_openpsa_projects_task_resource_dba::RESOURCE);
        $data['prospects'] = $qb->execute();

        midcom::get()->skip_page_style = true;
        $response = $this->show('show-prospects-xml');
        $response->headers->set('Content-Type', 'text/xml; charset=UTF-8');
        return $response;
    }

    public function _handler_prospect_slots(string $guid, array &$data)
    {
        $data['prospect'] = new org_openpsa_projects_task_resource_dba($guid);
        $data['person'] = new org_openpsa_contacts_person_dba($data['prospect']->person);
        $this->_task = new org_openpsa_projects_task_dba($data['prospect']->task);
        $this->_task->require_do('midgard:create');

        // Default to 15 minutes for minimum time here
        $minimum_time_slot = $this->_task->get_parameter('org.openpsa.projects.projectbroker', 'minimum_slot') ?: .25;
        $data['slots'] = org_openpsa_calendar_event_member_dba::find_free_times(
            ($minimum_time_slot * 60),
            $data['person'],
            $this->_task->start,
            $this->_task->end);

        midcom::get()->skip_page_style = true;

        return $this->show('show-prospect');
    }
}
