<?php
/**
 * @package org.openpsa.calendar
 * @author Nemein Oy, http://www.nemein.com/
 * @copyright Nemein Oy, http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * MidCOM wrapped class for access to stored queries
 *
 * @package org.openpsa.calendar
 */
class org_openpsa_calendar_resource_dba extends  midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_calendar_resource';

    function get_reservations($from, $to)
    {
        $qb = org_openpsa_calendar_event_resource_dba::new_query_builder();

        // Find all events that occur during [$from, $to]
        $qb->add_constraint("event.start", "<=", $to);
        $qb->add_constraint("event.end", ">=", $from);

        $qb->add_constraint('resource', '=', $this->id);

        $qb->add_order('event.start');

        return $qb->execute();
    }
}
