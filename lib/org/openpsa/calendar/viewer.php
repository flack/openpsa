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
     * Add common elements and settings
     */
    public function _on_handle($handler, array $args)
    {
        // Always run in uncached mode
        midcom::get()->cache->content->no_cache();
    }

    /**
     * @return array
     */
    public function get_calendar_options()
    {
        $options = [
            'businessHours' => [
                'start' => $this->_config->get('day_start_time') . ':00',
                'end' => $this->_config->get('day_end_time') . ':00',
                'dow' => [1, 2, 3, 4, 5]
            ]
        ];

        $prefix = '/org.openpsa.widgets/fullcalendar-3.2.0/';
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
