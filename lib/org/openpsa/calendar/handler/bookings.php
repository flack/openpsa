<?php
/**
 * @package org.openpsa.calendar
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_handler_bookings extends midcom_baseclasses_components_handler
{
    public function _handler_list(string $guid, array &$data)
    {
        $data['task'] = new org_openpsa_projects_task_dba($guid);
        $booked_time = 0;
        $booked_percentage = 100;

        $data['bookings'] = [
            'confirmed' => [],
            'suspected' => [],
        ];
        $mc = new org_openpsa_relatedto_collector($guid, org_openpsa_calendar_event_dba::class);
        $mc->add_object_order('start', 'ASC');
        $events = $mc->get_related_objects_grouped_by('status');

        foreach ($events as $status => $list) {
            if ($status == org_openpsa_relatedto_dba::CONFIRMED) {
                $data['bookings']['confirmed'] = $list;
            } else {
                $data['bookings']['suspected'] = $list;
            }
        }
        foreach ($data['bookings']['confirmed'] as $booking) {
            $booked_time += ($booking->end - $booking->start) / 3600;
        }

        $booked_time = round($booked_time);

        if ($data['task']->plannedHours != 0) {
            $booked_percentage = round(100 / $data['task']->plannedHours * $booked_time);
        }

        $data['booked_percentage'] = $booked_percentage;
        $data['booked_time'] = $booked_time;

        return $this->show('show-bookings');
    }
}
