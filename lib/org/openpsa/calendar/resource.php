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
    var $__midcom_class_name__ = __CLASS__;
    var $__mgdschema_class_name__ = 'org_openpsa_calendar_resource';

    static function new_query_builder()
    {
        return $_MIDCOM->dbfactory->new_query_builder(__CLASS__);
    }

    static function new_collector($domain, $value)
    {
        return $_MIDCOM->dbfactory->new_collector(__CLASS__, $domain, $value);
    }
    
    static function &get_cached($src)
    {
        return $_MIDCOM->dbfactory->get_cached(__CLASS__, $src);
    }
    
    function get_parent_guid_uncached()
    {
        if ($this->owner != 0)
        {
            $parent = new midcom_db_person($this->owner);
            return $parent->guid;
        }
        else
        {
            return null;
        }
    }

    function _on_creating()
    {
        if ($this->_check_duplicates($this->name))
        {
            midcom_connection::set_error(MGD_ERR_OBJECT_NAME_EXISTS);
            return false;
        }
        return true;
    }

    function _on_updating()
    {
        if ($this->_check_duplicates($this->name))
        {
            midcom_connection::set_error(MGD_ERR_OBJECT_NAME_EXISTS);
            return false;
        }
        return true;
    }

    function _check_duplicates($name)
    {
        if ($name == '')
        {
            return false;
        }

        // Check for duplicates
        $qb = org_openpsa_calendar_resource_dba::new_query_builder();
        $qb->add_constraint('name', '=', $name);

        if ($this->id)
        {
            $qb->add_constraint('id', '<>', $this->id);
        }

        $result = $qb->execute();
        if (count($result) > 0)
        {
            return true;
        }
        return false;
    }

    function get_reservations($from, $to)
    {
        $qb = org_openpsa_calendar_event_resource_dba::new_query_builder();

        // Find all events that occur during [$from, $end]
        $qb->begin_group("OR");
            // The event begins during [$from, $to]
            $qb->begin_group("AND");
                $qb->add_constraint("event.start", ">=", $from);
                $qb->add_constraint("event.start", "<=", $to);
            $qb->end_group();
            // The event begins before and ends after [$from, $to]
            $qb->begin_group("AND");
                $qb->add_constraint("event.start", "<=", $from);
                $qb->add_constraint("event.end", ">=", $to);
            $qb->end_group();
            // The event ends during [$from, $to]
            $qb->begin_group("AND");
                $qb->add_constraint("event.end", ">=", $from);
                $qb->add_constraint("event.end", "<=", $to);
            $qb->end_group();
        $qb->end_group();

        $qb->add_constraint('resource', '=', $this->id);

        $qb->add_order('event.start');

        return $qb->execute();
    }
}
?>