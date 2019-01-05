<?php
/**
 * @package org.openpsa.calendar
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\schemadb;

/**
 * Calendar create handler.
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_handler_event_create extends midcom_baseclasses_components_handler
{
    /**
     * The calendar root event
     *
     * @var org_openpsa_calendar_event_dba
     */
    private $root_event;

    private function load_controller(org_openpsa_calendar_conflictmanager $conflictmanager, array $args)
    {
        $resource = (isset($args[0])) ? $args[0] : midcom::get()->auth->user->guid;
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
        if (!empty($_GET['start'])) {
            $defaults['start'] = strtotime($_GET['start']);
            if (!empty($_GET['end'])) {
                $defaults['end']= strtotime($_GET['end']);
            } else {
                $defaults['end'] = $defaults['start'] + 3600;
            }
        }

        $schemadb = schemadb::from_path($this->_config->get('schemadb'));
        foreach ($schemadb->all() as $schema) {
            $schema->set('validation', [['callback' => [$conflictmanager, 'validate_form']]]);
        }

        $dm = new datamanager($schemadb);
        return $dm
            ->set_defaults($defaults)
            ->set_storage($event)
            ->get_controller();
    }

    /**
     * Handle the creation phase
     *
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     */
    public function _handler_create(array $args, array &$data)
    {
        $this->root_event = org_openpsa_calendar_interface::find_root_event();
        $this->root_event->require_do('midgard:create');

        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.calendar/calendar.js');
        midcom::get()->head->set_pagetitle($this->_l10n->get('create event'));

        $conflictmanager = new org_openpsa_calendar_conflictmanager(new org_openpsa_calendar_event_dba, $this->_l10n);
        // Load the controller instance
        $data['controller'] = $this->load_controller($conflictmanager, $args);

        $workflow = $this->get_workflow('datamanager', ['controller' => $data['controller']]);
        $response = $workflow->run();
        if ($workflow->get_state() == 'save') {
            $indexer = new org_openpsa_calendar_midcom_indexer($this->_topic);
            $indexer->index($data['controller']->get_datamanager());
            midcom::get()->head->add_jsonload('openpsa_calendar_widget.refresh();');
        }
        return $response;
    }
}
