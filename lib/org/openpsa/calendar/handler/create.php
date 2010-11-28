<?php
/**
 * @package org.openpsa.calendar
 * @author Nemein Oy http://www.nemein.com/
 * @version $Id: create.php 26627 2010-08-30 08:09:30Z flack $
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.calendar site interface class.
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_handler_create extends midcom_baseclasses_components_handler
{
    /**
     * Datamanager2 create controller
     *
     * @access private
     * @var midcom_helper_datamanager2_controller_create
     */
    var $_controller;
    
    /**
     * Defaults for the creation mode
     * 
     * @access private
     * @var Array
     */
    private $_defaults = array();
    
    /**
     * Load the creation controller
     * 
     * @access private
     */
    function _load_controller()
    {
        // Load schema database
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb'));

        $this->_controller = midcom_helper_datamanager2_controller::create('create');
        $this->_controller->schemadb =& $schemadb;
        $this->_controller->defaults = $this->_defaults;
        $this->_controller->callback_object =& $this;
        
        if (! $this->_controller->initialize())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to initialize a DM2 create controller.");
            // This will exit.
        }
    }
    
    /**
     * Event conflicts
     */
    function _event_resourceconflict_messages(&$conflict_event)
    {
        reset($conflict_event->busy_em);
        foreach ($conflict_event->busy_em as $pid => $events)
        {
            $person = org_openpsa_contacts_person_dba::get_cached($pid);
            if (   !is_object($person)
                || !$person->id)
            {
                continue;
            }
            debug_add("{$person->name} is busy, adding DM errors");
            reset($events);
            foreach ($events as $eguid)
            {
                //We might need sudo to get the event
                $_MIDCOM->auth->request_sudo();
                $event = new org_openpsa_calendar_event_dba($eguid);
                $_MIDCOM->auth->drop_sudo();
                if (   !is_object($event)
                    || !$event->id)
                {
                    continue;
                }
                //Then on_loaded checks again
                $event->_on_loaded();
                debug_add("{$person->name} is busy in event {$event->title}, appending error\n===\n" . sprintf('%s is busy in event "%s" (%s)', $person->name, $event->title, $event->format_timeframe()) . "\n===\n");
                $_MIDCOM->uimessages->add($this->_l10n->get('org.openpsa.calendar'), sprintf($this->_l10n->get('%s is busy in event \'%s\' (%s)'), $person->name, $event->title, $event->format_timeframe()), 'error');
            }
        }
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    function & dm2_create_callback (&$controller)
    {
        $this->_event = new org_openpsa_calendar_event_dba();
        $this->_event->up = $this->_root_event->id;
        if (! $this->_event->create())
        {
            debug_print_r('We operated on this object:', $this->_event);
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                'Failed to create a new event, cannot continue. Last Midgard error was: ' . midcom_connection::get_error_string());
            // This will exit.
        }

        return $this->_event;
    }

    /**
     * Handle the creation phase
     * 
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     * @return boolean              Indicating success
     */
    public function _handler_create($handler_id, $args, &$data)
    {
        // Get the root event
        $this->_root_event = org_openpsa_calendar_interface::find_root_event();
        
        // ACL handling: require create privileges
        $_MIDCOM->auth->require_user_do('midgard:create', null, 'org_openpsa_calendar_event_dba');
        
        if (isset($args[0]))
        {
            $this->_person = new midcom_db_person($args[0]);
            
            if (   $this->_person
                && $this->_person->guid)
            {
                $this->_defaults['participants'][$this->_person->id] = $this->_person;
            }
        }
        
        if (isset($args[1]))
        {
            $time = $args[1];
            
            if ($time)
            {
                $this->_defaults['start'] = $time;
                $this->_defaults['end'] = $time + 3600;
            }
        }
        
        // Load the controller instance
        $this->_load_controller();
        
        // Process form
        switch ($this->_controller->process_form())
        {
            case 'save':
            case 'cancel':
                $_MIDCOM->add_jsonload('window.opener.location.reload();');
                $_MIDCOM->add_jsonload('window.close();');
                break;
        }

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this); 
                
        // Hide the ROOT style
        $_MIDCOM->skip_page_style = true;
        
        return true;
    }
    
    /**
     * Show the create screen
     *
     * @param String $handler_id    Name of the request handler
     * @param array &$data          Public request data, passed by reference
     */
    public function _show_create($handler_id, &$data)
    {
        if (   array_key_exists('view', $this->_request_data)
            && $this->_request_data['view'] === 'conflict_handler')
        {
            $this->_request_data['popup_title'] = 'resource conflict';
            midcom_show_style('show-popup-header');
            $this->_request_data['event_dm'] =& $this->_controller;
            midcom_show_style('show-event-conflict');
            midcom_show_style('show-popup-footer');
        }
        else
        {
            // Set title to popup
            $this->_request_data['popup_title'] = $this->_l10n->get('create event');
            // Show popup
            midcom_show_style('show-popup-header');
            $this->_request_data['event_dm'] =& $this->_controller;
            midcom_show_style('show-event-new');
            midcom_show_style('show-popup-footer');
        }
    }
}
?>