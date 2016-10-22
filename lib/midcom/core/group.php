<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM group implementation supporting Midgard Groups.
 *
 * @package midcom
 */
class midcom_core_group
{
    /**
     * The storage object on which we are based. This is usually a midgard_group
     * directly, as this class has to work outside of the ACLs. It must not be used
     * from the outside.
     *
     * Access to this member is restricted to the ACL user/group core. In case you
     * need a real Storage object for this group, call get_storage() instead.
     *
     * @var midgard_group
     */
    protected $_storage = null;

    /**
     * Name of the group
     *
     * The variable is considered to be read-only.
     *
     * @var string
     */
    public $name = '';

    /**
     * The identification string used to internally identify the group uniquely
     * in the system. This is usually some kind of group:$guid string combination.
     *
     * The variable is considered to be read-only.
     *
     * @var string
     */
    public $id = '';

    /**
     * The scope value, which must be set during the _load callback, indicates the "depth" of the
     * group in the inheritance tree. This is used during privilege merging in the content
     * privilege code, which needs a way to determine the proper ordering. Top level groups
     * start with a scope of 1.
     *
     * The variable is considered to be read-only.
     *
     * @var integer
     */
    public $scope = MIDCOM_PRIVILEGE_SCOPE_ROOTGROUP;

    /**
     * Contains the parent of the current group, cached for repeated accesses.
     *
     * @var midcom_core_group
     */
    private $_cached_parent_group = null;

    /**
     * The constructor retrieves the group identified by its name from the database and
     * prepares the object for operation.
     *
     * It will use the Query Builder to retrieve a group by its name and populate the
     * $storage, $name and $id members accordingly.
     *
     * Any error will trigger midcom_error.
     *
     * @param mixed $id This is a valid identifier for the group to be loaded. Usually this is either
     *     a database ID or GUID for Midgard Groups or a valid complete MidCOM group identifier, which
     *     will work for all subclasses.
     */
    public function __construct($id = null)
    {
        if (is_null($id))
        {
            throw new midcom_error('The class midcom_core_group is not default constructible.');
        }

        if (   is_a($id, 'midcom_db_group')
            || is_a($id, 'midgard_group'))
        {
            $this->_storage = $id;
        }
        else
        {
            if (   is_string($id)
                 && substr($id, 0, 6) == 'group:')
            {
                $id = substr($id, 6);
            }
            else if (   is_numeric($id)
                     && $id == 0)
            {
                throw new midcom_error('0 is not a valid DB identifier');
            }
            try
            {
                $this->_storage = new midgard_group($id);
            }
            catch (Exception $e)
            {
                debug_add('Tried to load a midcom_core_group, but got error ' . $e->getMessage(), MIDCOM_LOG_ERROR);
                debug_print_r('Passed argument was:', $id);
                throw new midcom_error($e->getMessage());
            }
        }

        if ($this->_storage->official != '')
        {
            $this->name = $this->_storage->official;
        }
        else if ($this->_storage->name != '')
        {
            $this->name = $this->_storage->name;
        }
        else
        {
            $this->name = "Group #{$this->_storage->id}";
        }
        $this->id = "group:{$this->_storage->guid}";

        // Determine scope
        $parent = $this->get_parent_group();
        if (is_null($parent))
        {
            $this->scope = MIDCOM_PRIVILEGE_SCOPE_ROOTGROUP;
        }
        else
        {
            $this->scope = $parent->scope + 1;
        }
    }

    /**
     * Retrieves a list of groups owned by this group.
     *
     * @return midgard_group[] A list of group objects in which are owned by the current group
     */
    function list_subordinate_groups()
    {
        $qb = new midgard_query_builder('midgard_group');
        $qb->add_constraint('owner', '=', $this->_storage->id);
        return $qb->execute();
    }

    /**
     * Retrieves a list of users for which are a member in this group.
     *
     * @return midcom_core_user[] A list of user objects in which are members of the current group, indexed by their ID.
     */
    public function list_members()
    {
        $return = array();

        if (empty($this->_storage->id))
        {
            debug_add('$this->storage is not object or id is empty', MIDCOM_LOG_ERROR);
            return $return;
        }

        $qb = new midgard_query_builder('midgard_member');
        $qb->add_constraint('gid', '=', $this->_storage->id);
        $result = $qb->execute();

        foreach ($result as $member)
        {
            try
            {
                $user = new midcom_core_user($member->uid);
                $return[$user->id] = $user;
            }
            catch (midcom_error $e)
            {
                debug_add("The membership record {$member->id} is invalid, the user {$member->uid} failed to load.", MIDCOM_LOG_ERROR);
                debug_add('Last Midgard error was: ' . $e->getMessage());
                debug_print_r('Membership record was:', $member);
            }
        }

        return $return;
    }

    /**
     * Return a list of all groups in which the MidCOM user passed is a member.
     *
     * @param midcom_core_user $user The user that should be looked-up.
     * @return midcom_core_group[] Member groups, indexed by their ID.
     */
    public static function list_memberships($user)
    {
        $mc = new midgard_collector('midgard_member', 'uid.guid', $user->guid);
        $mc->add_constraint('gid', '<>', 0);
        $mc->set_key_property('gid');
        @$mc->execute();
        $result = $mc->list_keys();
        if (empty($result))
        {
            return array();
        }

        $return = array();
        foreach (array_keys($result) as $gid)
        {
            try
            {
                $group = new midcom_core_group($gid);
                $return[$group->id] = $group;
            }
            catch (midcom_error $e)
            {
                debug_add("The group {$gid} is unknown, skipping the membership record.", MIDCOM_LOG_ERROR);
                debug_add('Last Midgard error was: ' . midcom_connection::get_error_string());
            }
        }

        return $return;
    }

    /**
     * Returns the parent group.
     *
     * You must adhere the reference that is returned, otherwise the internal caching
     * and runtime state strategy will fail.
     *
     * @return midcom_core_group The parent group of the current group or null if there is none.
     */
    function get_parent_group()
    {
        if (is_null($this->_cached_parent_group))
        {
            if ($this->_storage->owner == 0)
            {
                return null;
            }

            if ($this->_storage->id == $this->_storage->owner)
            {
                debug_print_r('Broken Group', $this, MIDCOM_LOG_CRIT);
                throw new midcom_error('A group was its own parent, which will result in an infinite loop. See debug log for more info.');
            }

            $parent = new midgard_group();
            $parent->get_by_id($this->_storage->owner);

            if (!$parent->id)
            {
                debug_add("Could not load Group ID {$this->_storage->owner} from the database, aborting, this should not happen. See the debug level log for details. ("
                    . midcom_connection::get_error_string() . ')',
                    MIDCOM_LOG_ERROR);
                debug_print_r('Group that we started from is:', $this->_storage);
                return null;
            }

            $this->_cached_parent_group = midcom::get()->auth->get_group($parent);
        }
        return $this->_cached_parent_group;
    }

    /**
     * Return a list of privileges assigned directly to the group. The default implementation
     * queries the GUID directly using the get_self_privileges method of the
     * midcom_core_privilege class, which should work fine on all MgdSchema
     * objects. If the storage object is null, an empty array is returned.
     *
     * @return midcom_core_privilege[]
     */
    function get_privileges()
    {
        if (is_null($this->_storage))
        {
            return array();
        }
        return midcom_core_privilege::get_self_privileges($this->_storage->guid);
    }

    /**
     * Return a MidCOM DBA level storage object for the current group. Be aware,
     * that depending on ACL information, the retrieval of the user may fail.
     *
     * Also, as outlined in the member $_storage, not all groups may have a DBA object associated
     * with them, therefore this call may return null.
     *
     * The default implementation will return an instance of midcom_db_group based
     * on the member $this->_storage->id if that object is defined, or null otherwise.
     *
     * @return midcom_db_group A MidCOM DBA object that holds the information associated with
     *     this group, or null if there is no storage object.
     */
    public function get_storage()
    {
        if ($this->_storage === null)
        {
            return null;
        }
        return new midcom_db_group($this->_storage);
    }
}
