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

    public function _on_creating()
    {
        if ($this->_check_duplicates($this->name)) {
            midcom_connection::set_error(MGD_ERR_OBJECT_NAME_EXISTS);
            return false;
        }
        return true;
    }

    public function _on_updating()
    {
        if ($this->_check_duplicates($this->name)) {
            midcom_connection::set_error(MGD_ERR_OBJECT_NAME_EXISTS);
            return false;
        }
        return true;
    }

    private function _check_duplicates($name)
    {
        if ($name == '') {
            return false;
        }

        // Check for duplicates
        $qb = org_openpsa_calendar_resource_dba::new_query_builder();
        $qb->add_constraint('name', '=', $name);

        if ($this->id) {
            $qb->add_constraint('id', '<>', $this->id);
        }

        return ($qb->count() > 0);
    }

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
