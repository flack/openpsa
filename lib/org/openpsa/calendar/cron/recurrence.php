<?php
/**
 * @package org.openpsa.calendar
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Cron handler that generates occurrences of recurring events up to the configured horizon
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_cron_recurrence extends midcom_baseclasses_components_cron_handler
{
    public function execute()
    {
        if (!midcom::get()->auth->request_sudo($this->_component)) {
            $this->print_error("Could not get sudo, see error log for details");
            return;
        }
        $recurrence = new org_openpsa_calendar_recurrence;
        foreach ($recurrence->extend_all(org_openpsa_calendar_recurrence::get_horizon()) as $master) {
            $this->print_error("Could not extend event #{$master->id}: " . midcom_connection::get_error_string());
        }

        midcom::get()->auth->drop_sudo();
    }
}
