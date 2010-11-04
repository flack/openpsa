<?php
/**
 * @package midcom.db
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: group.php 24475 2009-12-16 12:05:15Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM Legacy Database Abstraction Layer
 *
 * This class encapsulates a classic MidgardGroup with its original features.
 *
 * <i>Preliminary Implementation:</i>
 *
 * Be aware that this implementation is incomplete, and grows on a is-needed basis.
 *
 * @package midcom.db
 */
class midcom_db_group extends midcom_baseclasses_database_group
{

    /**
     * The default constructor will create an empty object. Optionally, you can pass
     * an object ID or GUID to the object which will then initialize the object with
     * the corresponding DB instance.
     *
     * @param mixed $id A valid object ID or GUID, omit for an empty object.
     */
    function __construct($id = null)
    {
        parent::__construct($id);
    }

    /**
     * Overwrite the query builder getter with a version retrieving the right type.
     * We need a better solution here in DBA core actually, but it will be difficult to
     * do this as we cannot determine the current class in a polymorphic environment without
     * having a this (this call is static).
     * 
     * @static
     */
    static function new_query_builder()
    {
        return $_MIDCOM->dbfactory->new_query_builder(__CLASS__);
    }
    
    static function &get_cached($src)
    {
        return $_MIDCOM->dbfactory->get_cached(__CLASS__, $src);
    }

    /**
     * Helper function, adds the given person to this group. The current user must have
     * midgard:create privileges on this object for this to succeed. If the person is
     * already a member of this group, nothing is done.
     *
     * @param midcom_baseclasses_database_person The person to add.
     * @return boolean Indicating success.
     */
    function add_member($person)
    {
        $_MIDCOM->auth->require_do('midgard:create', $this);

        if ($this->is_member($person))
        {
            return true;
        }

        $member = new midcom_db_member();
        $member->gid = $this->id;
        $member->uid = $person->id;
        if (! $member->create())
        {
            return false;
        }

        // Adjust privileges, owner is the group in question.
        $member->set_privilege('midgard:owner', "group:{$this->guid}");
        $member->unset_privilege('midgard:owner');

        return true;
    }

    /**
     * Checks whether the given user is a member of this group.
     *
     * @param midcom_baseclasses_database_person The person to check.
     * @return boolean Indicating membership.
     */
    function is_member($person)
    {
        $qb = midcom_db_member::new_query_builder();
        $qb->add_constraint('gid', '=', $this->id);
        $qb->add_constraint('uid', '=', $person->id);
        $result = $qb->count();
        if($result == 0)
        {
            return false;
        }
        return true;
    }

}


?>