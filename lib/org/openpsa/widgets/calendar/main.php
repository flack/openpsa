<?php
/**
 * @package org.openpsa.widgets
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * fullcalendar-based calendar widget
 *
 * @package org.openpsa.widgets
 */
class org_openpsa_widgets_calendar
{
    private static $prefix = '/org.openpsa.widgets/fullcalendar-4.4.0/';

    public static function add_head_elements(array $views)
    {
        $head = midcom::get()->head;
        $head->add_jsfile(MIDCOM_STATIC_URL . self::$prefix . 'core/main.min.js');
        $head->add_jsfile(MIDCOM_STATIC_URL . self::$prefix . 'interaction/main.min.js');
        foreach ($views as $view) {
            $head->add_jsfile(MIDCOM_STATIC_URL . self::$prefix . $view . '/main.min.js');
        }
        if ($lang = self::get_lang()) {
            $head->add_jsfile(MIDCOM_STATIC_URL . self::$prefix . "core/locales/{$lang}.js");
        }

        $head->add_stylesheet(MIDCOM_STATIC_URL . self::$prefix . 'core/main.min.css');
        foreach ($views as $view) {
            $head->add_stylesheet(MIDCOM_STATIC_URL . self::$prefix . $view . '/main.min.css');
        }
        $head->add_stylesheet(MIDCOM_STATIC_URL . '/org.openpsa.calendar/calendar.css');

        $head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.calendar/calendar.js');
    }

    public static function get_lang() : ?string
    {
        $lang = midcom::get()->i18n->get_current_language();
        if (file_exists(MIDCOM_STATIC_ROOT . self::$prefix . "core/locales/{$lang}.js")) {
            return $lang;
        }
        $lang = midcom::get()->i18n->get_fallback_language();
        if (file_exists(MIDCOM_STATIC_ROOT . self::$prefix . "core/locales/{$lang}.js")) {
            return $lang;
        }
        return null;
    }
}
