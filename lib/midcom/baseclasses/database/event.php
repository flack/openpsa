<?php
/**
 * @package midcom.baseclasses
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: event.php 23975 2009-11-09 05:44:22Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard Event record with framework support.
 *
 * Note, as with all MidCOM DB layer objects, you should not use the get_by*
 * operations directly, instead, you have to use the constructor's $id parameter.
 *
 * Also, all QueryBuilder operations need to be done by the factory class
 * obtainable through the statically callable new_query_builder() DBA methods.
 *
 * @package midcom.baseclasses
 * @see midcom_services_dbclassloader
 */
class midcom_baseclasses_database_event extends midcom_core_dbaobject
{
    var $__midcom_class_name__ = __CLASS__;
    var $__mgdschema_class_name__ = 'midgard_event';

    function __construct($id = null)
    {
        parent::__construct($id);
    }

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

    /**
     * Returns the Parent of the Event. This can either be another event if we have
     * a reply event, or a topic otherwise.
     *
     * @return MidgardObject Parent object or NULL if there is none.
     */
    function get_parent_guid_uncached()
    {
        if ($this->up == 0)
        {
            return null;
        }

        $parent = new midcom_baseclasses_database_event($this->up);
        if (! $parent)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Could not load Event ID {$this->up} from the database, aborting.",
                MIDCOM_LOG_INFO);
            debug_pop();
            return null;
        }

        return $parent->guid;
    }

    /**
     * Deletes event membership records associated with this event.
     */
    function _on_deleted()
    {
        parent::_on_deleted();

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
                    debug_push_class(__CLASS__, __FUNCTION__);
                    debug_add("Failed to delete event membership record {$membership->id}, last Midgard error was: " . midcom_application::get_error_string(), MIDCOM_LOG_ERROR);
                    debug_pop();
                }
            }
        }
    }

}

?>