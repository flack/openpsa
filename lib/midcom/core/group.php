<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Doctrine\ORM\Query\Expr\Join;

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
    protected $_storage;

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
    public function __construct($id)
    {
        if ($id === null) {
            throw new midcom_error('The class midcom_core_group is not default constructible.');
        }

        if (is_a($id, midcom_db_group::class) || is_a($id, 'midgard_group')) {
            $this->_storage = $id;
        } else {
            if (is_string($id)) {
                $id_parts = explode(':', $id);
                if (count($id_parts) == 2) {
                    if ($id_parts[0] != 'group') {
                        throw new midcom_error("The group type identifier {$id_parts[0]} is unknown");
                    }
                    $id = $id_parts[1];
                }
            } elseif ($id == 0) {
                throw new midcom_error('0 is not a valid DB identifier');
            }
            try {
                $this->_storage = new midgard_group($id);
            } catch (Exception $e) {
                debug_add('Tried to load a midcom_core_group, but got error ' . $e->getMessage(), MIDCOM_LOG_ERROR);
                debug_print_r('Passed argument was:', $id);
                throw new midcom_error($e->getMessage());
            }
        }

        if ($this->_storage->official != '') {
            $this->name = $this->_storage->official;
        } elseif ($this->_storage->name != '') {
            $this->name = $this->_storage->name;
        } else {
            $this->name = "Group #{$this->_storage->id}";
        }
        $this->id = "group:{$this->_storage->guid}";

        // Determine scope
        if ($parent = $this->get_parent_group()) {
            $this->scope = $parent->scope + 1;
        }
    }

    /**
     * Retrieves a list of users for which are a member in this group.
     *
     * @return midcom_core_user[] A list of user objects in which are members of the current group, indexed by their ID.
     */
    public function list_members()
    {
        $return = [];

        if (empty($this->_storage->id)) {
            debug_add('$this->storage is not object or id is empty', MIDCOM_LOG_ERROR);
            return $return;
        }

        $qb = new midgard_query_builder(midcom::get()->config->get('person_class'));
        $qb->get_doctrine()
            ->leftJoin('midgard_member', 'm', Join::WITH, 'm.uid = c.id')
            ->where('m.gid = :id')
            ->setParameter('id', $this->_storage->id);

        foreach ($qb->execute() as $person) {
            $user = new midcom_core_user($person);
            $return[$user->id] = $user;
        }

        return $return;
    }

    /**
     * Return a list of all groups in which the MidCOM user passed is a member.
     *
     * @param midcom_core_user $user The user that should be looked up.
     * @return midcom_core_group[] Member groups, indexed by their ID.
     */
    public static function list_memberships(midcom_core_user $user)
    {
        $qb = new midgard_query_builder('midgard_group');
        $qb->get_doctrine()
            ->leftJoin('midgard_member', 'm', Join::WITH, 'm.gid = c.id')
            ->leftJoin('midgard_person', 'p', Join::WITH, 'm.uid = p.id')
            ->where('p.guid = :guid')
            ->setParameter('guid', $user->guid);

        $return = [];
        foreach ($qb->execute() as $group) {
            $return['group:' . $group->guid] = new static($group);
        }

        return $return;
    }

    /**
     * Returns the parent group.
     *
     * @return midcom_core_group|boolean The parent group of the current group or false if there is none.
     */
    function get_parent_group()
    {
        if ($this->_storage->owner == 0) {
            return false;
        }

        if ($this->_storage->id == $this->_storage->owner) {
            debug_print_r('Broken Group', $this, MIDCOM_LOG_CRIT);
            throw new midcom_error('A group was its own parent, which will result in an infinite loop. See debug log for more info.');
        }
        return midcom::get()->auth->get_group($this->_storage->owner);
    }

    /**
     * Return a list of privileges assigned directly to the group. The default implementation
     * queries the GUID directly using the get_self_privileges method of the
     * midcom_core_privilege class, which should work fine on all MgdSchema
     * objects. If the storage object is null, an empty array is returned.
     *
     * @return midcom_core_privilege[]
     */
    public function get_privileges()
    {
        if ($this->_storage === null) {
            return [];
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
        if ($this->_storage === null) {
            return null;
        }
        return new midcom_db_group($this->_storage);
    }
}
