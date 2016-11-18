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
     * @var string
     */
    private $resource;

    public function load_schemadb()
    {
        return midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb'));
    }

    public function get_schema_defaults()
    {
        $defaults = array('participants' => array());
        if ($person = midcom::get()->auth->get_user($this->resource)) {
            $person = $person->get_storage();
            $defaults['participants'][$person->id] = $person;
        } elseif ($group = midcom::get()->auth->get_group($this->resource)) {
            foreach ($group->list_members() as $member) {
                $person = $member->get_storage();
                $defaults['participants'][$person->id] = $person;
            }
        }

        if (!is_null($this->_requested_start)) {
            $defaults['start'] = $this->_requested_start;
            $defaults['end'] = $this->_requested_end;
        }
        return $defaults;
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    public function & dm2_create_callback(&$controller)
    {
        $this->_event = new org_openpsa_calendar_event_dba();
        $this->_event->up = $this->_root_event->id;
        if (!$this->_event->create()) {
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
        $this->_root_event = org_openpsa_calendar_interface::find_root_event();
        $this->_root_event->require_do('midgard:create');

        $this->resource = (isset($args[0])) ? $args[0] : midcom::get()->auth->user->guid;

        if (!empty($_GET['start'])) {
            $this->_requested_start = strtotime($_GET['start']);
            if (!empty($_GET['end'])) {
                $this->_requested_end = strtotime($_GET['end']);
            } else {
                $this->_requested_end = $this->_requested_start + 3600;
            }
        }

        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.calendar/calendar.js');
        midcom::get()->head->set_pagetitle($this->_l10n->get('create event'));

        $conflictmanager = new org_openpsa_calendar_conflictmanager(new org_openpsa_calendar_event_dba);
        // Load the controller instance
        $data['controller'] = $this->get_controller('create');
        $data['controller']->formmanager->form->addFormRule(array($conflictmanager, 'validate_form'));

        $workflow = $this->get_workflow('datamanager2', array('controller' => $data['controller']));
        $response = $workflow->run();
        if ($workflow->get_state() == 'save') {
            $indexer = new org_openpsa_calendar_midcom_indexer($this->_topic);
            $indexer->index($data['controller']->datamanager);
            midcom::get()->head->add_jsonload('openpsa_calendar_widget.refresh();');
        } elseif (!empty($conflictmanager->busy_members)) {
            midcom::get()->uimessages->add($this->_l10n->get('event conflict'), $conflictmanager->get_message($this->_l10n->get_formatter()), 'warning');
        }
        return $response;
    }
}
