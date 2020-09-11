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
    private static $prefix = '/org.openpsa.widgets/fullcalendar-5.3.0/';

    public static function add_head_elements(array $views)
    {
        $head = midcom::get()->head;
        $head->add_jsfile(MIDCOM_STATIC_URL . self::$prefix . 'lib/main.min.js');
        if ($lang = self::get_lang()) {
            $head->add_jsfile(MIDCOM_STATIC_URL . self::$prefix . "lib/locales/{$lang}.js");
        }

        $head->add_stylesheet(MIDCOM_STATIC_URL . self::$prefix . 'lib/main.min.css');
        $head->add_stylesheet(MIDCOM_STATIC_URL . '/org.openpsa.calendar/calendar.css');

        $head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.calendar/calendar.js');
    }

    public static function get_lang() : ?string
    {
        $lang = midcom::get()->i18n->get_current_language();
        if (file_exists(MIDCOM_STATIC_ROOT . self::$prefix . "lib/locales/{$lang}.js")) {
            return $lang;
        }
        $lang = midcom::get()->i18n->get_fallback_language();
        if (file_exists(MIDCOM_STATIC_ROOT . self::$prefix . "lib/locales/{$lang}.js")) {
            return $lang;
        }
        return null;
    }
}
