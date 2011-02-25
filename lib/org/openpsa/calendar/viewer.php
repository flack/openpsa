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
class org_openpsa_calendar_viewer extends midcom_baseclasses_components_request
{
    /**
     * Constructor.
     */
    public function _on_initialize()
    {
        if (!$this->_config->get('calendar_root_event'))
        {
            //We don't have a root event, reset request switch
            $this->_request_switch = array();
            $this->_request_switch['not_initialized'] = array
            (
                'handler' => 'notinitialized',
            );
        }
        else
        {
            //If you need any custom switches add them here
        }
    }

    /**
     * Add common elements and settings
     */
    public function _on_handle($handler, $args)
    {
        // Always run in uncached mode
        $_MIDCOM->cache->content->no_cache();

        $_MIDCOM->load_library('midcom.helper.datamanager2');

        $this->_request_data['view'] = 'default';

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/midcom.helper.datamanager2/jscript-calendar/calendar-win2k-1.css");

        return true;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_notinitialized($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_admin_user();

        if (org_openpsa_calendar_interface::find_root_event())
        {
            $_MIDCOM->relocate('');
        }
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_notinitialized($handler_id, &$data)
    {
        midcom_show_style('show-not-initialized');
    }

    public function _handler_frontpage($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();
        $selected_time = time();
        switch($this->_config->get('start_view'))
        {
            case 'day':
                $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX)
                . 'day/' . date('Y-m-d', $selected_time) . '/');
                // This will exit()
            break;
            case 'month':
                $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX)
                . 'month/' . date('Y-m-d', $selected_time) . '/');
                // This will exit()
                break;
            default:
            case 'week':
                $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX)
                . 'week/' . date('Y-m-d', $selected_time) . '/');
                // This will exit()
                break;
        }
    }
}
?>
