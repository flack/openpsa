<?php
/**
 * @package org.openpsa.calendar
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.calendar site interface class.
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_handler_admin extends midcom_baseclasses_components_handler
{
    /**
     * Datamanager2 instance
     *
     * @var midcom_helper_datamanager2_datamanager
     */
    private $_datamanager;

    /**
     * The event we're working on
     *
     * @var org_openpsa_calendar_event_dba
     */
    private $_event;

    public function _on_initialize()
    {
        midcom::get('auth')->require_valid_user();

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

        switch ($data['controller']->process_form())
        {
            case 'save':
                $indexer = new org_openpsa_calendar_midcom_indexer($this->_topic);
                $indexer->index($data['controller']->datamanager);
                //FALL-THROUGH
            case 'cancel':
                midcom::get('head')->add_jsonload('window.opener.location.reload();');
                midcom::get('head')->add_jsonload('window.close();');
                // This will _midcom_stop_request(well, in a way...)
        }

        // Add toolbar items
        org_openpsa_helpers::dm2_savecancel($this);
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
        $this->_request_data['popup_title'] = sprintf($this->_l10n->get('edit %s'), $this->_event->title);

        // Show popup
        midcom_show_style('show-popup-header');
        midcom_show_style('show-event-edit');
        midcom_show_style('show-popup-footer');
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
        $this->_event->require_do('midgard:delete');
        $this->_request_data['delete_succeeded'] = false;

        // Cancel pressed
        if (isset($_POST['org_openpsa_calendar_delete_cancel']))
        {
            midcom::get()->relocate("event/{$this->_event->guid}/");
            // This will exit
        }

        // Delete confirmed, remove the event
        if (isset($_POST['org_openpsa_calendar_deleteok']))
        {
            $this->_request_data['delete_succeeded'] = true;
            $this->_event->delete();
            midcom::get('head')->add_jsonload('window.opener.location.reload();');
            midcom::get('head')->add_jsonload('window.close();');
        }
        $this->_request_data['event'] =& $this->_event;
    }

    /**
     * Show event delete interface
     *
     * @param String $handler_id    Name of the request handler
     * @param array &$data          Public request data, passed by reference
     */
    public function _show_delete($handler_id, array &$data)
    {
        // Set title to popup
        if ($this->_request_data['delete_succeeded'])
        {
            $this->_request_data['popup_title'] = sprintf($this->_l10n->get('event %s deleted'), $this->_event->title);
        }
        else
        {
            $this->_request_data['popup_title'] = $this->_l10n->get('delete event');
        }

        // Show popup
        midcom_show_style('show-popup-header');
        $this->_request_data['event_dm'] =& $this->_datamanager;
        midcom_show_style('show-event-delete');
        midcom_show_style('show-popup-footer');
    }
}
?>