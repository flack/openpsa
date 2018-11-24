<?php
/**
 * @package org.openpsa.calendar
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\schemadb;
use midcom\datamanager\datamanager;

/**
 * org.openpsa.calendar site interface class.
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_handler_event_admin extends midcom_baseclasses_components_handler
{
    /**
     * The event we're working on
     *
     * @var org_openpsa_calendar_event_dba
     */
    private $_event;

    public function _on_initialize()
    {
        midcom::get()->auth->require_valid_user();
    }

    /**
     * Handle the editing phase
     *
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        // Get the event
        $this->_event = new org_openpsa_calendar_event_dba($args[0]);
        $this->_event->require_do('midgard:update');

        $conflictmanager = new org_openpsa_calendar_conflictmanager($this->_event, $this->_l10n);
        $schemadb = schemadb::from_path($this->_config->get('schemadb'));
        foreach ($schemadb->all() as $schema) {
            $schema->set('validation', [['callback' => [$conflictmanager, 'validate_form']]]);
        }
        $dm = new datamanager($schemadb);
        $data['controller'] = $dm
            ->set_storage($this->_event)
            ->get_controller();

        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.calendar/calendar.js');
        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_event->title));

        $workflow = $this->get_workflow('datamanager', ['controller' => $data['controller']]);

        $response = $workflow->run();
        if ($workflow->get_state() == 'save') {
            $indexer = new org_openpsa_calendar_midcom_indexer($this->_topic);
            $indexer->index($data['controller']->get_datamanager());
            midcom::get()->head->add_jsonload('openpsa_calendar_widget.refresh();');
        }
        return $response;
    }

    /**
     * Handle AJAX move
     *
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     */
    public function _handler_move($handler_id, array $args, array &$data)
    {
        if (empty($_POST['start'])) {
            throw new midcom_error('Incomplete request');
        }
        $event = new org_openpsa_calendar_event_dba($args[0]);
        $event->require_do('midgard:update');
        $start = strtotime($_POST['start']);
        //workaround for https://github.com/fullcalendar/fullcalendar/issues/3037
        if (empty($_POST['end'])) {
            $end = $event->end + ($start - $event->start);
        } else {
            $end = strtotime($_POST['end']);
        }
        $event->start = $start;
        $event->end = $end;
        if (!$event->update()) {
            throw new midcom_error('Update failed:' . midcom_connection::get_error_string());
        }
        return new midcom_response_json;
    }

    /**
     * Handle the delete phase
     *
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        // Get the event
        $this->_event = new org_openpsa_calendar_event_dba($args[0]);
        $workflow = $this->get_workflow('delete', ['object' => $this->_event]);
        return $workflow->run();
    }
}
