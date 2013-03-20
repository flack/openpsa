<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM Legacy Database Abstraction Layer
 *
 * This class encapsulates a classic MidgardEvent with its original features.
 *
 * <i>Preliminary Implementation:</i>
 *
 * Be aware that this implementation is incomplete, and grows on a is-needed basis.
 *
 * @package midcom.db
 */
class midcom_db_event extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midgard_event';

    public function get_label()
    {
        if ($this->start == 0)
        {
            return $this->title;
        }
        else
        {
            return strftime('%x', $this->start) . " {$this->title}";
        }
    }

    /**
     * Deletes event membership records associated with this event.
     */
    public function _on_deleted()
    {
        // Delete event memberships
        $qb = midcom_db_eventmember::new_query_builder();
        $qb->add_constraint('eid', '=', $this->id);
        $result = $qb->execute();
        if ($result)
        {
            foreach ($result as $membership)
            {
                if (! $membership->delete())
                {
                    debug_add("Failed to delete event membership record {$membership->id}, last Midgard error was: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                }
            }
        }
    }

    /**
     * Returns a prepared query builder which lists all eventmember records for this event.
     * No translation to persons is done.
     *
     * @return midcom_core_querybuilder A prepared QB instance.
     */
    function get_event_members_qb()
    {
        $qb = midcom_db_eventmember::new_query_builder();
        $qb->add_constraint('eid', '=', $this->id);
        return $qb;
    }

    /**
     * Returns an unsorted list of event members for this event.
     *
     * @return Array A list of midcom_db_eventmembers.
     */
    function list_event_members()
    {
        $qb = $this->get_event_members_qb();
        return $qb->execute();
    }
}
?>