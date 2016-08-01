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
    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_day($handler_id, array $args, array &$data)
    {
        // Generate start/end timestamps for the day
        $date = new DateTime($args[0]);
        $date->setTime(0, 0, 0);
        $from = clone $date;
        $date->setTime(23, 59, 59);
        $to = $date->getTimestamp();

        // List user's event memberships
        $mc = org_openpsa_calendar_event_member_dba::new_collector('uid', midcom_connection::get_user());

        // Find all events that occur during [$from, $to]
        $mc->add_constraint('eid.start', '<=', $to);
        $mc->add_constraint('eid.end', '>=', $from->getTimestamp());

        $eventmembers = $mc->get_values('eid');
        if (!empty($eventmembers))
        {
            $qb = org_openpsa_calendar_event_dba::new_query_builder();
            $qb->add_constraint('id', 'IN', $eventmembers);
            $data['events'] = $qb->execute();
        }
        else
        {
            $data['events'] = array();
        }
        $data['from'] = $from;
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
