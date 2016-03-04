<?php
/**
 * @package org.openpsa.calendar
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\workflow\delete;

/**
 * org.openpsa.calendar site interface class.
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_handler_admin extends midcom_baseclasses_components_handler
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

        // This is a popup
        midcom::get()->skip_page_style = true;
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

        // Load schema database
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb'));

        // Load the controller
        $data['controller'] = midcom_helper_datamanager2_controller::create('simple');
        $data['controller']->schemadb = $schemadb;
        $data['controller']->set_storage($this->_event);
        if (!$data['controller']->initialize())
        {
            throw new midcom_error("Failed to initialize a DM2 controller instance for article {$this->_article->id}.");
        }
        $data['conflictmanager'] = new org_openpsa_calendar_conflictmanager($this->_event);
        $data['controller']->formmanager->form->addFormRule(array($data['conflictmanager'], 'validate_form'));

        switch ($data['controller']->process_form())
        {
            case 'save':
                $indexer = new org_openpsa_calendar_midcom_indexer($this->_topic);
                $indexer->index($data['controller']->datamanager);
                //FALL-THROUGH
            case 'cancel':
                midcom::get()->head->add_jsonload("if (window.opener.openpsa_calendar_instance) window.opener.openpsa_calendar_instance.fullCalendar('refetchEvents');");
                midcom::get()->head->add_jsonload('window.close();');
                // This will exit (well, in a way...)
        }
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
        if (empty($_POST['start']))
        {
            throw new midcom_error('Incomplete request');
        }
        $event = new org_openpsa_calendar_event_dba($args[0]);
        $event->require_do('midgard:update');
        $start = strtotime($_POST['start']);
        //workaround for https://github.com/fullcalendar/fullcalendar/issues/3037
        if (empty($_POST['end']))
        {
            $end = $event->end + ($start - $event->start);
        }
        else
        {
            $end = strtotime($_POST['end']);
        }
        $event->start = $start;
        $event->end = $end;
        if (!$event->update())
        {
            throw new midcom_error('Update failed:' . midcom_connection::get_error_string());
        }
        return new midcom_response_json;
    }

    /**
     * Show event editing interface
     *
     * @param String $handler_id    Name of the request handler
     * @param array &$data          Public request data, passed by reference
     */
    public function _show_edit($handler_id, array &$data)
    {
        // Set title to popup
        $this->_request_data['title'] = sprintf($this->_l10n->get('edit %s'), $this->_event->title);

        // Show popup
        midcom_show_style('popup-head');
        if (!empty($data['conflictmanager']->busy_members))
        {
            midcom_show_style('show-event-conflict');
        }
        midcom_show_style('show-event-edit');
        midcom_show_style('popup-foot');
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
        $workflow = new delete($this->_event);
        $workflow->run();
        if ($workflow->get_state() === delete::SUCCESS)
        {
            midcom::get()->head->add_jsonload('window.opener.location.reload();');
            midcom::get()->head->add_jsonload('window.close();');
        }
    }

    /**
     * Show event delete interface
     *
     * @param String $handler_id    Name of the request handler
     * @param array &$data          Public request data, passed by reference
     */
    public function _show_delete($handler_id, array &$data)
    {
        midcom_show_style('popup-head');
        midcom_show_style('popup-foot');
    }
}
