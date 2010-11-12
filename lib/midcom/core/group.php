<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: group.php 26507 2010-07-06 13:31:06Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * System group, does not directly implement anything, it serves only as a base classes
 * for the various types of groups supported within the MidCOM framework.
 *
 * @package midcom
 */
class midcom_core_group extends midcom_baseclasses_core_object
{
    /**
     * The storage object on which we are based. This is usually a midgard_group
     * directly, as this class has to work outside of the ACLs. It must not be used
     * from the outside.
     *
     * Be aware, that this object may be null, depending on the actual implementation.
     *
     * Access to this member is restricted to the ACL user/group core. In case you
     * need a real Storage object for this group, call get_storage() instead.
     *
     * @var midgard_group
     * @access protected
     */
    var $_storage = null;

    /**
     * Name of the group
     *
     * The variable is considered to be read-only.
     *
     * @var string
     * @access public
     */
    var $name = '';

    /**
     * The identification string used to internally identify the group uniquely
     * in the system. This is usually some kind of group:$guid string combination.
     *
     * The variable is considered to be read-only.
     *
     * @var string
     * @access public
     */
    var $id = '';

    /**
     * The scope value, which must be set during the _load callback, indicates the "depth" of the
     * group in the inheritance tree. This is used during privilege merging in the content
     * privilege code, which needs a way to determine the proper ordering. Top level groups
     * start with a scope of 1.
     *
     * The variable is considered to be read-only.
     *
     * @var integer
     * @access public
     */
    var $scope = MIDCOM_PRIVILEGE_SCOPE_ROOTGROUP;

    /**
     * The constructor retrieves the group identified by its name from the database and
     * prepares the object for operation.
     *
     * This default constructor will call the _load method to actually retrieve anything,
     * so you should be fine by just calling this default constructor in your subclass
     * constructors.
     *
     * @param mixed $id This is a valid identifier for the group to be loaded. Usually this is either
     *     a database ID or GUID for Midgard Groups or a valid complete MidCOM group identifier, which
     *     will work for all subclasses.
     */
    function __construct($id = null)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        parent::__construct();

        if (is_null($id))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'The class midcom_core_group is not default constructible.');
            // This will exit.
        }

        if (! $this->_load($id))
        {
            debug_pop();
            $x =& $this;
            $x = false;
            return false;
        }
        debug_pop();
    }

    /**
     * Helper function that will look up a group and assign the object to the $storage
     * member.
     *
     * Must be overridden: The code should populate $storage with the actually retrieved
     * storage object, $name with the name of the group and $id with the group identifier
     * that will also be used within the privilege_io reader.
     *
     * @param mixed $id This is a valid identifier for the group to be loaded. Usually this is either
     *     a database ID or GUID for Midgard Groups or a valid complete MidCOM group identifier, which
     *     will work for all subclasses.
     * @return boolean Indicating success.
     */
    function _load($id)
    {
        _midcom_stop_request('The method ' . __CLASS__ . '::' . __FUNCTION__ . 'must be overridden.');
    }
    
    /**
     * Retrieves a list of groups owned by this group.
     *
     * @return Array A list of midcom_core_group objects in which are owned by the current group, false on failure.
     */
    function list_subordinate_groups()
    {
         return array();
    }

    /**
     * Retrieves a list of users for which are a member in this group.
     *
     * Must be overridden.
     *
     * @return Array A list of midcom_core_user objects in which are members of the current group, false on failure.
     */
    function list_members()
    {
         _midcom_stop_request('The method ' . __CLASS__ . '::' . __FUNCTION__ . 'must be overridden.');
    }

    /**
     * This method returns a list of all groups in which the
     * MidCOM user passed is a member.
     *
     * This function is always called statically.
     *
     * @param midcom_core_user $user The user that should be looked-up.
     * @return Array An array of member groups or false on failure.
     * @static
     */
    function list_memberships($user)
    {
        _midcom_stop_request('The method ' . __CLASS__ . '::' . __FUNCTION__ . 'must be overridden.');
    }

    /**
     * Returns the parent group.
     *
     * The default implementation assumes that there is no parent group.
     *
     * @return midcom_core_group The parent group of the current group or NULL if there is none.
     */
    function get_parent_group()
    {
        return null;
    }

    /**
     * Return a list of privileges assigned directly to the group. The default implementation
     * queries the storage object directly using the get_privileges method of the
     * midcom_core_baseclasses_core_dbobject class, which should work fine on all MgdSchema
     * objects. If the storage object is null, an empty array is returned.
     *
     * @return Array A list of midcom_core_privilege objects.
     */
    function get_privileges()
    {
        if (is_null($this->_storage))
        {
            return Array();
        }
        return midcom_core_privilege::get_self_privileges($this->_storage->guid);
    }

    /**
     * This function will return a MidCOM DBA level storage object for the current group. Be aware,
     * that depending on ACL information, the retrieval of the user may fail.
     *
     * Also, as outlined in the member $_storage, not all groups may have a DBA object associated
     * with them, therefore this call may return null.
     *
     * The default implementation will return an instance of midcom_db_group based
     * on the member $this->_storage->id if that object is defined, or null otherwise.
     *
     * @return MidgardObject Any MidCOM DBA level object that holds the information associated with
     *     this group, or null if there is no storage object.
     */
    function get_storage()
    {
        if ($this->_storage === null)
        {
            return null;
        }
        return new midcom_db_group($this->_storage);
    }


}



?>