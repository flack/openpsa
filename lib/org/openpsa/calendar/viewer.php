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
        if (!$this->_config->get('calendar_root_event')) {
            $stat = false;
            if (midcom::get()->auth->can_user_do('midgard:create', null, 'org_openpsa_calendar_event_dba')) {
                $stat = org_openpsa_calendar_interface::find_root_event();
            }

            if (!$stat) {
                midcom::get()->auth->require_admin_user();
            }
        }
    }

    /**
     * Add common elements and settings
     */
    public function _on_handle($handler, array $args)
    {
        // Always run in uncached mode
        midcom::get()->cache->content->no_cache();

        return true;
    }

    /**
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     */
    public function _handler_frontpage($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_valid_user();
        $selected_time = time();
        switch ($this->_config->get('start_view')) {
            case 'day':
                return new midcom_response_relocate('agendaDay/' . date('Y-m-d', $selected_time) . '/');

            case 'month':
                return new midcom_response_relocate('month/' . date('Y-m-d', $selected_time) . '/');

            default:
            case 'week':
                return new midcom_response_relocate('agendaWeek/' . date('Y-m-d', $selected_time) . '/');
        }
    }

    /**
     * @return array
     */
    public function get_calendar_options()
    {
        $options = array(
            'businessHours' => array(
                'start' => $this->_config->get('day_start_time') . ':00',
                'end' => $this->_config->get('day_end_time') . ':00',
                'dow' => array(1, 2, 3, 4, 5)
            )
        );

        $prefix = '/org.openpsa.widgets/fullcalendar-3.0.0/';
        $lang = midcom::get()->i18n->get_current_language();
        if (!file_exists(MIDCOM_STATIC_ROOT . $prefix . "locale/{$lang}.js")) {
            $lang = midcom::get()->i18n->get_fallback_language();
            if (!file_exists(MIDCOM_STATIC_ROOT . $prefix . "locale/{$lang}.js")) {
                $lang = false;
            }
        }

        if ($lang) {
            $options['lang'] = $lang;
        }

        return $options;
    }
}
