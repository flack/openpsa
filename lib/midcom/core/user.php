<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midgard\portable\api\error\exception as mgd_exception;

/**
 * System user, basically encapsulates a MidgardPerson. It does not provide a way to
 * manipulate accounts, instead, this is an abstraction used in the ACL system.
 *
 * You must not create these objects directly. Instead, use the factory method
 * midcom::get()->auth->get_user($id), where $id is any valid constructor argument
 * for a midcom_db_person.
 *
 * @package midcom
 */
class midcom_core_user
{
    /**
     * The storage object on which we are based.
     *
     * This is no MidCOM DBA layer object since it must not do any Access Control
     * for the internal system to work. The instance may not be accessed from the outside.
     *
     * Access to this member is restricted to the ACL user/group core. In case you
     * need a real Storage object for this group, call get_storage() instead.
     *
     * @var midgard_person
     */
    protected $_storage;

    /**
     * Username of the current user, it is to be considered read-only.
     */
    public string $username;

    /**
     * The full name of the current user.
     *
     * Built from the first and last name of the user record, falling back
     * to the username if both are unset. It is to be considered read-only.
     */
    public string $name;

    /**
     * The full reversed name of the current user.
     *
     * Built from the first and last name of the user record, falling back
     * to the username if both are unset. It is to be considered read-only.
     */
    public string $rname;

    /**
     * Lists all groups in which a user is a member, both directly and indirectly.
     *
     * There is no hierarchy, just a plain listing of midcom_core_group objects.
     * It is to be considered read-only.
     *
     * The array is indexed by the group identifiers, which are used to perform
     * in_group checks.
     *
     * It is loaded on demand.
     *
     * @var midcom_core_group[]
     */
    private $_all_groups;

    /**
     * Lists all groups in which a user is an immediate member.
     *
     * It is to be considered read-only.
     *
     * The array is indexed by the group identifiers, which are used to perform
     * in_group checks.
     *
     * It is loaded on demand.
     *
     * @var midcom_core_group[]
     */
    private $_direct_groups;

    /**
     * All groups the user is a member in, ordered by their inheritance chain.
     *
     * The first element in the array is always the top-level group, while the last
     * one is always a member of $_direct_groups. This is therefore a multilevel array and is
     * indexed by the direct group id's (midcom_core_group id's, not Midgard IDs!) of the
     * direct groups. The values are group identifiers as well, which can be resolved by either
     * get_group or using the all_groups listing.
     *
     * This member is populated with $_all_groups.
     *
     * @var array
     */
    private array $_inheritance_chains;

    /**
     * List of all privileges assigned to that user. It is to be considered read-only.
     *
     * Array keys are the privilege names, the values are the Privilege states (ALLOW/DENY).
     *
     * It is loaded on demand.
     *
     * @var array
     */
    private $_privileges;

    /**
     * List of all privileges assigned to that user based on the class he is accessing. It is to
     * be considered read-only.
     *
     * This is a multi level array. It holds regular privilege name/state arrays indexed by the
     * name of the class (or subtype thereof) for which they should apply.
     *
     * It is loaded on demand.
     *
     * @var array
     */
    private $_per_class_privileges;

    /**
     * The identification string used to internally identify the user uniquely
     * in the system.
     *
     * This is usually some kind of user:$guid string combination.
     */
    public string $id;

    /**
     * The GUID identifying this user, made directly available for easier linking.
     */
    public string $guid;

    /**
     * The scope value, which must be set during the _load callback, indicates the
     * "depth" of the group in the inheritance tree.
     *
     * This is used during privilege merging in the content privilege code, which
     * needs a way to determine the proper ordering. All persons currently
     * use the magic value -1.
     *
     * The variable is considered to be read-only.
     */
    public int $scope = MIDCOM_PRIVILEGE_SCOPE_USER;

    /**
     * The constructor retrieves the user identified by its name from the database and
     * prepares the object for operation.
     *
     * The class relies on the Midgard Framework to ensure the uniqueness of a user.
     *
     * The class is only intended to operate with users and groups, and should not be used
     * in normal operations regarding persons.
     *
     * @param mixed $id This is either a Midgard Person ID or GUID, a midcom_user ID or an already instantiated midgard_person.
     */
    public function __construct($id)
    {
        $person_class = midcom::get()->config->get('person_class');

        if (is_string($id)) {
            $this->_storage = $this->_load_from_string($id, $person_class);
        } elseif (is_numeric($id)) {
            if ($id < 1) {
                throw new midcom_error($id . ' is not a valid database ID');
            }
            try {
                $this->_storage = new $person_class($id);
            } catch (mgd_exception $e) {
                debug_add("Failed to retrieve the person ID {$id}: " . $e->getMessage(), MIDCOM_LOG_INFO);
                throw new midcom_error_midgard($e, $id);
            }
        } elseif (is_a($id, midcom_db_person::class) || is_a($id, $person_class)) {
            $this->_storage = $id;
        } else {
            debug_print_r('Passed argument was:', $id);
            throw new midcom_error('Tried to load a midcom_core_user, but $id was of unknown type.');
        }

        if (empty($this->_storage->guid)) {
            debug_print_r('Passed argument was:', $id);
            debug_print_r('_storage is:', $this->_storage);
            throw new midcom_error('storage GUID is not set');
        }

        $account = new midcom_core_account($this->_storage);

        $this->username = $account->get_username();
        $this->name = trim("{$this->_storage->firstname} {$this->_storage->lastname}");
        $this->rname = trim("{$this->_storage->lastname}, {$this->_storage->firstname}");
        if (empty($this->name)) {
            $this->name = $this->username;
            $this->rname = $this->username;
        }
        $this->id = "user:{$this->_storage->guid}";
        $this->guid = $this->_storage->guid;
    }

    private function _load_from_string(string $id, string $person_class)
    {
        // Don't even try with the magic assignees
        if (in_array($id, ['ANONYMOUS', 'EVERYONE', 'USERS', 'OWNER', 'SELF'])) {
            throw new midcom_error('Cannot instantiate magic assignees');
        }

        if (str_starts_with($id, 'user:')) {
            $id = substr($id, 5);
        }

        if (mgd_is_guid($id)) {
            try {
                return new $person_class($id);
            } catch (mgd_exception $e) {
                debug_add("Failed to retrieve the person GUID {$id}: " . $e->getMessage(), MIDCOM_LOG_INFO);
                throw new midcom_error_midgard($e, $id);
            }
        }
        debug_print_r('Passed argument was:', $id);
        throw new midcom_error('Tried to load a midcom_core_user, but $id was of unknown type.');
    }

    /**
     * Retrieves a list of groups for which this user is an immediate member.
     *
     * @return midcom_core_group[] A list of groups in which the current user is a member
     */
    public function list_memberships() : array
    {
        if ($this->_direct_groups === null) {
            $this->_load_direct_groups();
        }
        return $this->_direct_groups;
    }

    /**
     * Retrieves a list of groups for which this user is a member, both directly and indirectly.
     *
     * There is no hierarchy in the resultset, it is just a plain listing.
     *
     * @return midcom_core_group[] A list of groups in which the current user is a member
     */
    function list_all_memberships() : array
    {
        if ($this->_all_groups === null) {
            $this->_load_all_groups();
        }
        return $this->_all_groups;
    }

    /**
     * Returns the complete privilege set assigned to this user, taking all
     * parent groups into account.
     *
     * @return array Array keys are the privilege names, the values are the Privilege states (ALLOW/DENY).
     */
    public function get_privileges() : array
    {
        $this->_load_privileges();
        return $this->_privileges;
    }

    /**
     * Returns the specific per class global privilege set assigned to this user, taking all
     * parent groups into account.
     *
     * @return array Array keys are the privilege names, the values are the Privilege states (ALLOW/DENY).
     */
    public function get_per_class_privileges() : array
    {
        $this->_load_privileges();
        return $this->_per_class_privileges;
    }

    /**
     * Loads all groups the user is a direct member and assigns them to the _direct_groups member.
     */
    private function _load_direct_groups()
    {
        $this->_direct_groups = midcom_core_group::list_memberships($this);
    }

    /**
     * Get the GUID of the user's first group. This is used mainly to populate
     * the owner field during DBa object create calls
     */
    public function get_first_group_guid() : ?string
    {
        if ($this->_direct_groups !== null) {
            if (empty($this->_direct_groups)) {
                // User is not member of any groups
                return null;
            }
            return $this->_direct_groups[key($this->_direct_groups)]->get_storage()->guid;
        }

        //Not yet initialized, try to load one midgard group
        $mc = new midgard_collector('midgard_member', 'uid', $this->_storage->id);
        $mc->set_key_property('gid');
        $mc->set_limit(1);
        $mc->execute();
        $result = $mc->list_keys();
        if (!empty($result)) {
            if ($group = midcom::get()->auth->get_group(key($result))) {
                return $group->get_storage()->guid;
            }
        }

        $this->_load_all_groups();

        if (!empty($this->_direct_groups)) {
            return $this->_direct_groups[key($this->_direct_groups)]->get_storage()->guid;
        }

        return null;
    }

    /**
     * Loads the complete group hierarchy the user is a member in.
     */
    private function _load_all_groups()
    {
        if ($this->_direct_groups === null) {
            $this->_load_direct_groups();
        }

        $this->_all_groups = [];
        $this->_inheritance_chains = [];

        foreach ($this->_direct_groups as $id => $group) {
            $this->_all_groups[$id] =& $this->_direct_groups[$id];
            $inheritance_chain = [$group->id];
            /**
             * FIXME: Parent group members should inherit permissions from
             * the child groups, not the other way around!!!
            $parent = $group->get_parent_group();
            while ($parent !== null)
            {
                $this->_all_groups[$parent->id] = $parent;
                array_unshift($inheritance_chain, $parent->id);

                $parent = $parent->get_parent_group();
            }
            */
            $this->_inheritance_chains[$id] = $inheritance_chain;
        }
    }

    /**
     * Load the privileges from the database.
     *
     * This uses the inheritance chains
     * loaded by _load_all_groups().
     */
    private function _load_privileges()
    {
        static $cache = [];

        if (!array_key_exists($this->id, $cache)) {
            debug_add("Loading privileges for user {$this->name} ({$this->id})");

            if ($this->_all_groups === null) {
                $this->_load_all_groups();
            }

            $this->_privileges = [];
            $this->_per_class_privileges = [];

            foreach ($this->_inheritance_chains as $inheritance_chain) {
                // Compute permissions based on this group line.
                foreach ($inheritance_chain as $group_id) {
                    $this->_merge_privileges($this->_all_groups[$group_id]->get_privileges());
                }
            }

            // Finally, apply our own privilege set to the one we got from the group
            $this->_merge_privileges(midcom_core_privilege::get_self_privileges($this->guid));
            $cache[$this->id]['general'] = $this->_privileges;
            $cache[$this->id]['class'] = $this->_per_class_privileges;
        } else {
            $this->_privileges = $cache[$this->id]['general'];
            $this->_per_class_privileges = $cache[$this->id]['class'];
        }
    }

    /**
     * Merge privileges helper.
     *
     * It loads the privileges of the given object and
     * loads all "SELF" assignee privileges into the class.
     *
     * @param midcom_core_privilege[] $privileges A list of privilege records, see mRFC 15 for details.
     */
    private function _merge_privileges(array $privileges)
    {
        foreach ($privileges as $privilege) {
            if ($privilege->value == MIDCOM_PRIVILEGE_ALLOW) {
                $action = 'Grant';
                $this->merge_privilege($privilege);
            } elseif ($privilege->value == MIDCOM_PRIVILEGE_DENY) {
                $action = 'Revoke';
                $this->merge_privilege($privilege);
            } else {
                $action = 'Inherit';
            }

            $message = $action . ' ' . $privilege->privilegename;
            if ($privilege->classname != '') {
                $message .= ' for class hierarchy ' . $privilege->classname;
            }
            debug_add($message);
        }
    }

    private function merge_privilege(midcom_core_privilege $privilege)
    {
        if ($privilege->classname != '') {
            $this->_per_class_privileges[$privilege->classname][$privilege->privilegename] = $privilege->value;
        } else {
            $this->_privileges[$privilege->privilegename] = $privilege->value;
        }
    }

    /**
     * Checks whether a user is a member of the given group.
     *
     * The group argument may be one of the following (checked in this order of precedence):
     *
     * 1. A valid group object (subclass of midcom_core_group)
     * 2. A group string identifier, matching the regex ^group:
     * 3. A valid midcom group name
     *
     * @param mixed $group Group to check against, this can be either a midcom_core_group object or a group string identifier.
     */
    public function is_in_group($group) : bool
    {
        if ($this->_all_groups === null) {
            $this->_load_all_groups();
        }

        // Process
        if ($group instanceof midcom_core_group) {
            return array_key_exists($group->id, $this->_all_groups);
        }
        if (str_starts_with($group, 'group:')) {
            return array_key_exists($group, $this->_all_groups);
        }
        // We scan through our groups looking for a midgard group with the right name
        foreach ($this->_all_groups as $group_object) {
            if ($group_object->get_storage()->name == $group) {
                return true;
            }
        }

        return false;
    }

    public function is_admin() : bool
    {
        $account = new midcom_core_account($this->_storage);
        return $account->is_admin();
    }

    /**
     * Return the MidCOM DBA object for the current user.
     * Be aware that depending on ACL information, the retrieval of the user may fail.
     */
    public function get_storage() : midcom_db_person
    {
        return new midcom_db_person($this->_storage);
    }

    /**
     *
     * Checks the online state of the user. You require the privilege midcom:isonline
     * for the storage object you are going to check. The privilege is not granted by default,
     * to allow users full control over their privacy.
     *
     * 'unknown' is returned in cases where you have insufficient permissions.
     *
     * @return string One of 'online', 'offline' or 'unknown', indicating the current online
     *     state.
     */
    public function is_online() : string
    {
        $person = $this->get_storage();
        if (!$person->can_do('midcom:isonline')) {
            return 'unknown';
        }

        $timeout = midcom::get()->config->get('auth_login_session_timeout', 0);
        $last_seen = $person->get_parameter('midcom', 'online');
        if (   !$last_seen
            || ($timeout > 0 && time() - $timeout > $last_seen)) {
            return 'offline';
        }
        return 'online';
    }

    /**
     * Returns the last login of the given user.
     *
     * You require the privilege midcom:isonline for the storage object you are
     * going to check. The privilege is not granted by default, to allow users
     * full control over their privacy.
     *
     * null is returned in cases where you have insufficient permissions.
     */
    public function get_last_login() : ?int
    {
        $person = $this->get_storage();
        if (!$person->can_do('midcom:isonline')) {
            return null;
        }

        return (int) $person->get_parameter('midcom', 'last_login');
    }

    /**
     * Returns the first login time of the user, if available.
     *
     * In contrast to get_last_login and is_online this query does not require
     * the isonline privilege, as it is usually used to determine the "age"
     * of a user account in a community.
     *
     * @return int The time of the first login, or zero in case of users which have never
     *     logged in.
     */
    public function get_first_login() : int
    {
        return (int) $this->_storage->get_parameter('midcom', 'first_login');
    }

    /**
     * Deletes the current user account and the person record.
     */
    public function delete() : bool
    {
        $person = $this->get_storage();
        $account = new midcom_core_account($person);

        if (!$account->delete()) {
            debug_add('Failed to delete the account, last Midgard error was: ' . midcom_connection::get_error_string(), MIDCOM_LOG_INFO);
            return false;
        }

        return $person->delete();
    }
}
