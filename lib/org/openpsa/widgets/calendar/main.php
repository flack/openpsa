<?php
/**
 * @package org.openpsa.widgets
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * fullcandar-based calendar widget
 *
 * @package org.openpsa.widgets
 */
class org_openpsa_widgets_calendar extends midcom_baseclasses_components_purecode
{
    public static function add_head_elements()
    {
        $head = midcom::get()->head;
        $prefix = '/org.openpsa.widgets/fullcalendar-3.0.0/';
        $head->add_jsfile(MIDCOM_STATIC_URL . $prefix . 'lib/moment.min.js');
        $head->add_jsfile(MIDCOM_STATIC_URL . $prefix . 'fullcalendar.min.js');

        $lang = midcom::get()->i18n->get_current_language();
        if (!file_exists(MIDCOM_STATIC_ROOT . $prefix . "locale/{$lang}.js")) {
            $lang = midcom::get()->i18n->get_fallback_language();
            if (!file_exists(MIDCOM_STATIC_ROOT . $prefix . "locale/{$lang}.js")) {
                $lang = false;
            }
        }

        if ($lang) {
            $head->add_jsfile(MIDCOM_STATIC_URL . $prefix . "locale/{$lang}.js");
        }

        $head->add_stylesheet(MIDCOM_STATIC_URL . $prefix . 'fullcalendar.min.css');
        $head->add_stylesheet(MIDCOM_STATIC_URL . $prefix . 'fullcalendar.print.css', 'print');
        $head->add_stylesheet(MIDCOM_STATIC_URL . '/org.openpsa.calendar/calendar.css');

        $head->add_jsfile(MIDCOM_STATIC_URL . '/jQuery/history.js-1.8.0/jquery.history.js');
        $head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.calendar/calendar.js');
    }
}
