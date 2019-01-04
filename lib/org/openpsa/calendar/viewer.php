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
class org_openpsa_calendar_viewer extends midcom_baseclasses_components_viewer
{
    /**
     * Add common elements and settings
     */
    public function _on_handle($handler, array $args)
    {
        // Always run in uncached mode
        midcom::get()->cache->content->no_cache();
    }
}
