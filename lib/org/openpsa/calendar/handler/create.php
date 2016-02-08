<?php
/**
 * @package org.openpsa.calendar
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.calendar site interface class.
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_handler_create extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_create
{
    /**
     * The requested start time
     *
     * @var int
     */
    private $_requested_start;

    /**
     * The requested end time
     *
     * @var int
     */
    private $_requested_end;

    /**
     * @var midcom_db_person
     */
    private $_person;

    public function load_schemadb()
    {
        return midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb'));
    }

    public function get_schema_defaults()
    {
        $defaults = array();
        if (!empty($this->_person->guid))
        {
            $defaults['participants'][$this->_person->id] = $this->_person;
        }

        if (!is_null($this->_requested_start))
        {
            $defaults['start'] = $this->_requested_start;
            $defaults['end'] = $this->_requested_end;
        }
        return $defaults;
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    public function & dm2_create_callback (&$controller)
    {
        $this->_event = new org_openpsa_calendar_event_dba();
        $this->_event->up = $this->_root_event->id;
        if (!$this->_event->create())
        {
            debug_print_r('We operated on this object:', $this->_event);
            throw new midcom_error('Failed to create a new event. Last Midgard error was: ' . midcom_connection::get_error_string());
        }

        return $this->_event;
    }

    /**
     * Handle the creation phase
     *
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        // Get the root event
        $this->_root_event = org_openpsa_calendar_interface::find_root_event();

        // ACL handling: require create privileges
        $this->_root_event->require_do('midgard:create');

        $this->_person = midcom::get()->auth->user->get_storage();

        if (!empty($_GET['start']))
        {
            $this->_requested_start = strtotime($_GET['start']);
            if (!empty($_GET['end']))
            {
                $this->_requested_end = strtotime($_GET['end']);
            }
            else
            {
                $this->_requested_end = $this->_requested_start + 3600;
            }
        }

        // Load the controller instance
        $data['controller'] = $this->get_controller('create');
        $data['conflictmanager'] = new org_openpsa_calendar_conflictmanager(new org_openpsa_calendar_event_dba);
        $data['controller']->formmanager->form->addFormRule(array($data['conflictmanager'], 'validate_form'));

        // Process form
        switch ($data['controller']->process_form())
        {
            case 'save':
                $indexer = new org_openpsa_calendar_midcom_indexer($this->_topic);
                $indexer->index($data['controller']->datamanager);
                //FALL-THROUGH
            case 'cancel':
                midcom::get()->head->add_jsonload("window.opener.openpsa_calendar_instance.fullCalendar('refetchEvents');");
                midcom::get()->head->add_jsonload('window.close();');
                break;
        }

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);

        // Hide the ROOT style
        midcom::get()->skip_page_style = true;
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.calendar/calendar.js');
        midcom::get()->head->add_jsonload('openpsa_calendar_widget.setup();');
    }

    /**
     * Show the create screen
     *
     * @param String $handler_id    Name of the request handler
     * @param array &$data          Public request data, passed by reference
     */
    public function _show_create($handler_id, array &$data)
    {
        // Set title to popup
        $this->_request_data['popup_title'] = $this->_l10n->get('create event');
        // Show popup
        midcom_show_style('show-popup-header');
        if (!empty($data['conflictmanager']->busy_members))
        {
            midcom_show_style('show-event-conflict');
        }
        midcom_show_style('show-event-new');
        midcom_show_style('show-popup-footer');
    }
}
