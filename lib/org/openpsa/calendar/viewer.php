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
        midcom::get('cache')->content->no_cache();

        $this->_request_data['view'] = 'default';

        return true;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_notinitialized($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_admin_user();

        if (org_openpsa_calendar_interface::find_root_event())
        {
            return new midcom_response_relocate('');
        }
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_notinitialized($handler_id, array &$data)
    {
        midcom_show_style('show-not-initialized');
    }

    public function _handler_frontpage($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user();
        $selected_time = time();
        switch($this->_config->get('start_view'))
        {
            case 'day':
                return new midcom_response_relocate('day/' . date('Y-m-d', $selected_time) . '/');

            case 'month':
                return new midcom_response_relocate('month/' . date('Y-m-d', $selected_time) . '/');

            default:
            case 'week':
                return new midcom_response_relocate('week/' . date('Y-m-d', $selected_time) . '/');
        }
    }
}
?>
