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
    public function _on_initialize()
    {
        midcom::get()->auth->require_valid_user();
    }

    /**
     * Handle the editing phase
     *
     * @param string $guid The object's GUID
     * @param array &$data          Public request data, passed by reference
     */
    public function _handler_edit($guid, array &$data)
    {
        $event = new org_openpsa_calendar_event_dba($guid);
        $event->require_do('midgard:update');

        $conflictmanager = new org_openpsa_calendar_conflictmanager($event, $this->_l10n);
        $schemadb = schemadb::from_path($this->_config->get('schemadb'));
        foreach ($schemadb->all() as $schema) {
            $schema->set('validation', [['callback' => [$conflictmanager, 'validate_form']]]);
        }
        $dm = new datamanager($schemadb);
        $data['controller'] = $dm
            ->set_storage($event)
            ->get_controller();

        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.calendar/calendar.js');
        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $event->title));

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
     * @param string $guid The object's GUID
     */
    public function _handler_move($guid)
    {
        if (empty($_POST['start'])) {
            throw new midcom_error('Incomplete request');
        }
        $event = new org_openpsa_calendar_event_dba($guid);
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
     * @param string $guid The object's GUID
     */
    public function _handler_delete($guid)
    {
        $event = new org_openpsa_calendar_event_dba($guid);
        $workflow = $this->get_workflow('delete', ['object' => $event]);
        return $workflow->run();
    }
}
