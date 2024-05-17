<?php
/**
 * @package org.openpsa.calendar
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\schemadb;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
use midcom\datamanager\controller;

/**
 * Calendar create handler.
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_handler_event_create extends midcom_baseclasses_components_handler
{
    private org_openpsa_calendar_event_dba $root_event;

    private function load_controller(ParameterBag $query, org_openpsa_calendar_validator $validator, ?string $resource) : controller
    {
        $resource = $resource ?: midcom::get()->auth->user->guid;
        $event = new org_openpsa_calendar_event_dba();
        $event->up = $this->root_event->id;

        $defaults = ['participants' => []];
        if ($person = midcom::get()->auth->get_user($resource)) {
            $person = $person->get_storage();
            $defaults['participants'][] = $person->id;
        } elseif ($group = midcom::get()->auth->get_group($resource)) {
            foreach ($group->list_members() as $member) {
                $person = $member->get_storage();
                $defaults['participants'][] = $person->id;
            }
        }
        if ($query->has('start')) {
            $defaults['start'] = strtotime(substr($query->get('start'), 0, 19)) + 1;
            if ($query->has('end')) {
                $defaults['end']= strtotime(substr($query->get('end'), 0, 19));
            } else {
                $defaults['end'] = $defaults['start'] + 3600;
            }
        }
        $schemadb = schemadb::from_path($this->_config->get('schemadb'));
        foreach ($schemadb->all() as $schema) {
            $schema->set('validation', [['callback' => $validator->validate(...)]]);
        }

        $dm = new datamanager($schemadb);
        return $dm
            ->set_defaults($defaults)
            ->set_storage($event)
            ->get_controller();
    }

    /**
     * Handle the creation phase
     */
    public function _handler_create(Request $request, array &$data, ?string $resource = null)
    {
        $this->root_event = org_openpsa_calendar_interface::find_root_event();
        $this->root_event->require_do('midgard:create');

        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.calendar/calendar.js');
        midcom::get()->head->set_pagetitle($this->_l10n->get('create event'));

        $validator = new org_openpsa_calendar_validator(new org_openpsa_calendar_event_dba, $this->_l10n);
        // Load the controller instance
        $data['controller'] = $this->load_controller($request->query, $validator, $resource);

        $workflow = $this->get_workflow('datamanager', ['controller' => $data['controller'], 'relocate' => false]);
        $response = $workflow->run($request);
        if ($workflow->get_state() == 'save') {
            $indexer = new org_openpsa_calendar_midcom_indexer($this->_topic);
            $indexer->index($data['controller']->get_datamanager());
        }
        return $response;
    }
}
