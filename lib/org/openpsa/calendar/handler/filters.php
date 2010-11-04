<?php
/**
 * @package org.openpsa.calendar
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: filters.php 23975 2009-11-09 05:44:22Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Calendar filters handler
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_handler_filters extends midcom_baseclasses_components_handler
{
    function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Handle the AJAX request
     * 
     * @todo This function is unused
     */
    function _handle_ajax()
    {
        $update_succeeded = false;
        $errstr = NULL;
        $user = new midcom_db_person($this->_request_data['user']->id);
        if (array_key_exists('org_openpsa_calendar_filters_add', $_POST))
        {
            $target = new midcom_db_person($_POST['org_openpsa_calendar_filters_add']);
            if ($target)
            {
                $update_succeeded = $user->parameter('org_openpsa_calendar_show', $_POST['org_openpsa_calendar_filters_add'], 1);
            }
            $errstr = midcom_application::get_error_string();
        }
        else if (array_key_exists('org_openpsa_calendar_filters_remove', $_POST))
        {
            $target = new midcom_db_person($_POST['org_openpsa_calendar_filters_remove']);
            if ($target)
            {
                $update_succeeded = $user->parameter('org_openpsa_calendar_show', $_POST['org_openpsa_calendar_filters_remove'], '');
            }
            $errstr = midcom_application::get_error_string();
        }

        $ajax = new org_openpsa_helpers_ajax();
        //This will exit.
        $ajax->simpleReply($update_succeeded, $errstr);
    }

    /**
     * Handle the request for editing contact list
     * 
     * @access public
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     * @return boolean              Indicating success
     */
    function _handler_edit($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();
        
        // Get the current user
        $this->_person = new midcom_db_person($_MIDGARD['user']);
        $this->_person->require_do('midgard:update');
        
        // Load the schema database
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_filters'));
        
        // Load the controller
        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->set_storage($this->_person);
        if (! $this->_controller->initialize())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to initialize a DM2 controller instance for article {$this->_article->id}.");
            // This will exit.
        }
        
        // Process the form
        switch ($this->_controller->process_form())
        {
            case 'save':
            case 'cancel':
                if (isset($_GET['org_openpsa_calendar_returnurl']))
                {
                    $url = $_GET['org_openpsa_calendar_returnurl'];
                }
                else
                {
                    $url = '';
                }
                $_MIDCOM->relocate($url);
                // This will exit
        }
        
        // Add the breadcrumb pieces
        $tmp = array();
        
        if (isset($_GET['org_openpsa_calendar_returnurl']))
        {
            $tmp[] = array
            (
                MIDCOM_NAV_URL => $_GET['org_openpsa_calendar_returnurl'],
                MIDCOM_NAV_NAME => $this->_l10n->get('calendar'),
            );
        }
        
        $tmp[] = array
        (
            MIDCOM_NAV_URL => 'filters/',
            MIDCOM_NAV_NAME => $this->_l10n->get('choose calendars'),
        );
        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);

        return true;
    }

    /**
     * Show the contact editing interface
     * 
     * @access public
     * @param String $handler_id    Name of the request handler
     * @param array &$data          Public request data, passed by reference
     */
    function _show_edit($handler_id, &$data)
    {
        $data['controller'] =& $this->_controller;
        $data['person'] =& $this->_person;
        
        midcom_show_style('calendar-filter-chooser');
    }
}
?>