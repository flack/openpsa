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
        $until = org_openpsa_calendar_recurrence::get_horizon();

        $qb = org_openpsa_calendar_event_dba::new_query_builder();
        $qb->add_constraint('up', '=', org_openpsa_calendar_interface::find_root_event()->id);
        $qb->add_constraint('recurrence', '<>', '');
        $qb->add_constraint('materializedUntil', '<', $until);
        // Only series that continue beyond what has been generated so far
        $qb->begin_group('OR');
            $qb->add_constraint('recurrenceUntil', '=', 0);
            $qb->add_constraint_with_property('recurrenceUntil', '>', 'materializedUntil');
        $qb->end_group();

        $recurrence = new org_openpsa_calendar_recurrence;
        foreach ($qb->execute() as $master) {
            if (!$recurrence->extend($master, $until)) {
                $this->print_error("Could not extend event #{$master->id}: " . midcom_connection::get_error_string());
            }
        }

        midcom::get()->auth->drop_sudo();
    }
}
