<?php
/**
 * @package org.openpsa.calendar
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Calendar agenda handler
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_handler_agenda extends midcom_baseclasses_components_handler
{
    public function _on_initialize()
    {
        $_MIDCOM->load_library('org.openpsa.calendarwidget');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_day($handler_id, array $args, array &$data)
    {
        // Generate start/end timestamps for the day
        $requested_time = @strtotime($args[0]);
        if (!$requested_time)
        {
            throw new midcom_error('could not generate time from ' . $args[0]);
        }

        // Use calendarwidget for time calculations
        $this->_request_data['calendar'] = new org_openpsa_calendarwidget(date('Y', $requested_time), date('m', $requested_time), date('d', $requested_time));
        $this->_request_data['calendar']->type = ORG_OPENPSA_CALENDARWIDGET_DAY;

        $from = $this->_request_data['calendar']->get_day_start();
        $to = $this->_request_data['calendar']->get_day_end();

        // List user's event memberships
        $mc = midcom_db_eventmember::new_collector('uid', midcom_connection::get_user());

        // Find all events that occur during [$from, $end]
        $mc->begin_group("OR");
            // The event begins during [$from, $to]
            $mc->begin_group("AND");
                $mc->add_constraint("eid.start", ">=", $from);
                $mc->add_constraint("eid.start", "<=", $to);
            $mc->end_group();
            // The event begins before and ends after [$from, $to]
            $mc->begin_group("AND");
                $mc->add_constraint("eid.start", "<=", $from);
                $mc->add_constraint("eid.end", ">=", $to);
            $mc->end_group();
            // The event ends during [$from, $to]
            $mc->begin_group("AND");
                $mc->add_constraint("eid.end", ">=", $from);
                $mc->add_constraint("eid.end", "<=", $to);
            $mc->end_group();
        $mc->end_group();

        $eventmembers = $mc->get_values('eid');
        $this->_request_data['events'] = array();
        foreach ($eventmembers as $eid)
        {
            $this->_request_data['events'][] = new org_openpsa_calendar_event_dba($eid);
        }
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_day($handler_id, array &$data)
    {
        midcom_show_style('show-day-header');

        foreach ($this->_request_data['events'] as $event)
        {
            $this->_request_data['event'] = $event;
            midcom_show_style('show-day-item');
        }

        midcom_show_style('show-day-footer');
    }
}
?>