<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM level replacement for the Midgard Eventmember record with framework support.
 *
 * An event member has its event as explicit parent, *not* its person.
 *
 * Note, as with all MidCOM DB layer objects, you should not use the get_by*
 * operations directly, instead, you have to use the constructor's $id parameter.
 *
 * Also, all QueryBuilder operations need to be done by the factory class
 * obtainable through the statically callable new_query_builder() DBA methods.
 *
 * @see midcom_services_dbclassloader
 * @package midcom.db
 */
class midcom_db_eventmember extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'midgard_eventmember';

    /**
     * Overwrite the query builder getter with a version retrieving the right type.
     * We need a better solution here in DBA core actually, but it will be difficult to
     * do this as we cannot determine the current class in a polymorphic environment without
     * having a this (this call is static).
     */
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

    public function get_label()
    {
        $person = new midcom_db_person($this->uid);
        $event = new midcom_db_event($this->eid);
        return sprintf($_MIDCOM->i18n->get_string('%s in %s', 'midcom'), $person->name, $event->title);
    }

    /**
     * Returns the Parent of the Eventmember. This is the event it is assigned to.
     *
     * @return MidgardObject Parent object or null if there is none.
     */
    function get_parent_guid_uncached()
    {
        if ($this->eid == 0)
        {
            return null;
        }

        try
        {
            $parent = new midcom_db_event($this->eid);
        }
        catch (midcom_error $e)
        {
            debug_add("Could not load Event ID {$this->eid} from the database, aborting.",
                MIDCOM_LOG_INFO);
            return null;
        }

        return $parent->guid;
    }
}
?>