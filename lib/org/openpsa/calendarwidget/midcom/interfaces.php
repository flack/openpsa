<?php
/**
 * OpenPSA calendar widget for displaying week, month and day calendars
 *
 * Startup loads main class, which is used for all operations.
 *
 * @package org.openpsa.calendarwidget
 * @author Henri Bergius, http://bergie.iki.fi
 * @version $Id: interfaces.php 22916 2009-07-15 09:53:28Z flack $
 * @copyright Nemein Oy, http://www.nemein.com
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package org.openpsa.calendarwidget
 */
class org_openpsa_calendarwidget_interface extends midcom_baseclasses_components_interface
{
    function _on_initialize()
    {
        // Constants for the rendering styles we need
        if (!defined('ORG_OPENPSA_CALENDARWIDGET_MONTH'))
        {
            define('ORG_OPENPSA_CALENDARWIDGET_MONTH', 1);
        }
        if (!defined('ORG_OPENPSA_CALENDARWIDGET_WEEK'))
        {
            define('ORG_OPENPSA_CALENDARWIDGET_WEEK', 2);
        }
        if (!defined('ORG_OPENPSA_CALENDARWIDGET_DAY'))
        {
            define('ORG_OPENPSA_CALENDARWIDGET_DAY', 3);
        }

        // Resource types
        if (!defined('ORG_OPENPSA_CALENDARWIDGET_RESOURCE_PERSON'))
        {
            define('ORG_OPENPSA_CALENDARWIDGET_RESOURCE_PERSON', 6);
        }
        if (!defined('ORG_OPENPSA_CALENDARWIDGET_RESOURCE_ROOM'))
        {
            define('ORG_OPENPSA_CALENDARWIDGET_RESOURCE_ROOM', 7);
        }
        if (!defined('ORG_OPENPSA_CALENDARWIDGET_RESOURCE_CAR'))
        {
            define('ORG_OPENPSA_CALENDARWIDGET_RESOURCE_CAR', 8);
        }
        if (!defined('ORG_OPENPSA_CALENDARWIDGET_RESOURCE_MISC'))
        {
            define('ORG_OPENPSA_CALENDARWIDGET_RESOURCE_MISC', 8);
        }

        // Make the calendar pretty
        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . "/org.openpsa.calendarwidget/calendarwidget.css",
            )
        );
        return true;
    }
}
?>