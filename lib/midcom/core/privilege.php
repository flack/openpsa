<?php
/**
 * @package midcom
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Privilege class, used to interact with the privilege system. It encapsulates the actual
 * Database Level Object. As usual with MidCOM DBA, you <i>must never access the DB layer
 * object.</i>
 *
 * The main area of expertise of this class is privilege IO (loading and storing), their
 * validation and privilege merging.
 *
 * It is important to understand that you must never load privilege records directly, or
 * access them by their IDs. Instead, use the DBA level interface functions to locate
 * existing privilege sets. The only time where you use this class directly is when
 * creating new privilege, using the default constructor of this class (although the
 * create_new_privilege_object DBA member methods are the preferred way of doing this).
 *
 * <b>Caching:</b>
 *
 * This class uses the memcache cache module to speed up ACL accesses. It caches the ACL
 * objects retrieved from the database, not any merged privilege set (at this time, that is).
 * This should speed up regular operations quite a bit (along with the parent guid cache,
 * which is a second important key).
 *
 * @property string $objectguid GUID of the object the privilege applies to
 * @property string $privilegename Name of the privilege (for example `midgard:create`)
 * @property string $assignee Assignee of the privilege, for instance user or group identifier
 * @property string $classname MgdSchema class the privilege applies to, in case of class-level privileges
 * @property integer $value
                Value of the privilege:

                - 1: MIDCOM_PRIVILEGE_ALLOW
                - 2: MIDCOM_PRIVILEGE_DENY
                - 3: MIDCOM_PRIVILEGE_INHERIT

 * @property string $guid
 * @package midcom
 */
class midcom_core_privilege
{
    /**
     * Cached actual midcom_core_privilege_db data for this privilege.
     *
     * @var array
     */
    private $__privilege = [
        'guid' => '',
        'objectguid' => '',
        'privilegename'=> '',
        'assignee' => null,
        'scope' => -1,
        'classname' => '',
        'value' => null
    ];

    /**
     * The actual midcom_core_privilege_db object for this privilege.
     *
     * @var midcom_core_privilege_db
     */
    private $__privilege_object;

    /**
     * GUID of the midcom_core_privilege_db object, used when values are retrieved via collector instead of QB
     *
     * @var string
     */
    private $__guid = '';

    /**
     * Cached content object, based on $objectguid.
     *
     * @var midcom_core_dbaobject
     */
    private $__cached_object;

    /**
     * The Default constructor creates an empty privilege, if you specify
     * another privilege object in the constructor, a copy is constructed.
     *
     * @param midcom_core_privilege_db|array $src Object to copy from.
     */
    public function __construct($src = null)
    {
        if (is_array($src)) {
            // Store given values to our privilege array
            $this->__privilege = array_merge($this->__privilege, $src);
        } else {
            $this->_load($src);
            if ($src !== null) {
                $this->_sync_from_db_object();
            }
        }
    }

    // Magic getter and setter for object property mapping
    public function __get($property)
    {
        return $this->__privilege[$property] ?? null;
    }

    public function __set($property, $value)
    {
        $this->__privilege[$property] = $value;
    }

    public function __isset($property)
    {
        return isset($this->__privilege[$property]);
    }

    /**
     * Get the object referenced by the guid value of this privilege.
     */
    private function get_object() : ?midcom_core_dbaobject
    {
        if ($this->__cached_object === null) {
            try {
                $this->__cached_object = midcom::get()->dbfactory->get_object_by_guid($this->objectguid);
            } catch (midcom_error $e) {
                return null;
            }
        }
        return $this->__cached_object;
    }

    /**
     * Set a privilege to a given content object.
     */
    public function set_object(midcom_core_dbaobject $object)
    {
        $this->__cached_object = $object;
        $this->objectguid = $object->guid;
    }

    /**
     * Determine whether a given privilege applies for the given
     * user in content mode. This means, that all SELF privileges are skipped at this point,
     * EVERYONE privileges apply always, and all other privileges are checked against the
     * user.
     */
    public function does_privilege_apply(string $user_id) : bool
    {
        switch ($this->__privilege['assignee']) {
            case 'EVERYONE':
                return true;
            case 'ANONYMOUS':
                return in_array($user_id, ['EVERYONE', 'ANONYMOUS']);
            case 'USERS':
                return !in_array($user_id, ['EVERYONE', 'ANONYMOUS']);
            default:
                if ($this->__privilege['assignee'] == $user_id) {
                    return true;
                }
                if (strstr($this->__privilege['assignee'], 'group:') !== false) {
                    if ($user = midcom::get()->auth->get_user($user_id)) {
                        return $user->is_in_group($this->__privilege['assignee']);
                    }
                }
                return false;
        }
    }

    /**
     * Returns the privilege's scope (or -1 for SELF and broken privileges)
     */
    public function get_scope() : int
    {
        if (defined('MIDCOM_PRIVILEGE_SCOPE_' . $this->__privilege['assignee'])) {
            return constant('MIDCOM_PRIVILEGE_SCOPE_' . $this->__privilege['assignee']);
        }
        if ($assignee = $this->get_assignee()) {
            return $assignee->scope;
        }
        debug_print_r('Could not resolve the assignee of this privilege', $this);

        return -1;
    }

    /**
     * If the assignee has an object representation (at this time, only users and groups have), this call
     * will return the assignee object held by the authentication service.
     *
     * Use is_magic_assignee to determine if you have an assignee object.
     *
     * @see midcom_services_auth::get_assignee()
     * @return midcom_core_user|midcom_core_group|null object as returned by the auth service, null on failure.
     */
    public function get_assignee() : ?object
    {
        if ($this->is_magic_assignee()) {
            return null;
        }

        return midcom::get()->auth->get_assignee($this->assignee);
    }

    /**
     * Checks whether the current assignee is a magic assignee or an object identifier.
     */
    public function is_magic_assignee(string $assignee = null) : bool
    {
        if ($assignee === null) {
            $assignee = $this->assignee;
        }
        return in_array($assignee, ['SELF', 'EVERYONE', 'USERS', 'ANONYMOUS', 'OWNER']);
    }

    /**
     * Set the assignee member string to the correct value to represent the
     * object passed, in general, this resolves users and groups to their strings and
     * leaves magic assignees intact.
     *
     * Possible argument types:
     *
     * - Any one of the magic assignees SELF, EVERYONE, ANONYMOUS, USERS.
     * - Any midcom_core_user or midcom_core_group object or subtype thereof.
     * - Any string identifier which can be resolved using midcom_services_auth::get_assignee().
     *
     * @param mixed $assignee An assignee representation as outlined above.
     */
    public function set_assignee($assignee) : bool
    {
        if (   is_a($assignee, midcom_core_user::class)
            || is_a($assignee, midcom_core_group::class)) {
            $this->assignee = $assignee->id;
        } elseif (is_string($assignee)) {
            if ($this->is_magic_assignee($assignee)) {
                $this->assignee = $assignee;
            } else {
                $tmp = midcom::get()->auth->get_assignee($assignee);
                if (!$tmp) {
                    debug_add("Could not resolve the assignee string '{$assignee}', see above for more information.", MIDCOM_LOG_INFO);
                    return false;
                }
                $this->assignee = $tmp->id;
            }
        } else {
            debug_add('Unknown type passed, aborting.', MIDCOM_LOG_INFO);
            debug_print_r('Argument was:', $assignee);
            return false;
        }

        return true;
    }

    /**
     * Validate the privilege for correctness of all set options. This includes:
     *
     * - A check against the list of registered privileges to ensure the existence of the
     *   privilege itself.
     * - A check for a valid and existing assignee, this includes a class existence check for classname restrictions
     *   for SELF privileges.
     * - A check for an existing content object GUID (this implicitly checks for midgard:read as well).
     * - Enough privileges of the current user to update the object's privileges (the user
     *   must have midgard:update and midgard:privileges for this to succeed).
     * - A valid privilege value.
     */
    public function validate() : bool
    {
        if (!midcom::get()->auth->acl->privilege_exists($this->privilegename)) {
            debug_add("The privilege name '{$this->privilegename}' is unknown to the system. Perhaps the corresponding component is not loaded?",
                MIDCOM_LOG_INFO);
            return false;
        }

        if (!in_array($this->value, [MIDCOM_PRIVILEGE_ALLOW, MIDCOM_PRIVILEGE_DENY, MIDCOM_PRIVILEGE_INHERIT])) {
            debug_add("Invalid privilege value '{$this->value}'.", MIDCOM_LOG_INFO);
            return false;
        }

        if ($this->classname != '') {
            if ($this->assignee != 'SELF') {
                debug_add("The classname parameter was specified without having the magic assignee SELF set, this is invalid.", MIDCOM_LOG_INFO);
                return false;
            }
            if (!class_exists($this->classname)) {
                debug_add("The class '{$this->classname}' is not found, the SELF magic assignee with class restriction is invalid therefore.", MIDCOM_LOG_INFO);
                return false;
            }
        }

        if (   !$this->is_magic_assignee()
            && !$this->get_assignee()) {
            debug_add("The assignee identifier '{$this->assignee}' is invalid.", MIDCOM_LOG_INFO);
            return false;
        }
        if (   $this->assignee == 'OWNER'
            && $this->privilegename == 'midgard:owner') {
            debug_add("Tried to assign midgard:owner to the OWNER magic assignee, this is invalid.", MIDCOM_LOG_INFO);
            return false;
        }

        $object = $this->get_object();
        if (!$object) {
            debug_add("Could not retrieve the content object with the GUID '{$this->objectguid}'; see the debug level log for more information.",
                MIDCOM_LOG_INFO);
            return false;
        }
        if (   !$object->can_do('midgard:update')
            || !$object->can_do('midgard:privileges')) {
            debug_add("Insufficient privileges on the content object with the GUID '{$this->__guid}', midgard:update and midgard:privileges required.",
                MIDCOM_LOG_INFO);
            return false;
        }

        return true;
    }

    /**
     * List all content privileges assigned to a given object.
     * Essentially, this will exclude all SELF style assignees.
     *
     * This function is for use in the authentication framework only.
     *
     * @return midcom_core_privilege[]
     */
    public static function get_content_privileges(string $guid) : array
    {
        return self::_get_privileges($guid, 'CONTENT');
    }

    /**
     * List all privileges assigned directly to a user or group.
     * These are all SELF privileges.
     *
     * This function is for use in the authentication framework only.
     *
     * @return midcom_core_privilege[]
     */
    public static function get_self_privileges(string $guid) : array
    {
        return self::_get_privileges($guid, 'SELF');
    }

    /**
     * List all privileges assigned an object unfiltered.
     *
     * This function is for use in the authentication framework only
     *
     * @return midcom_core_privilege[]
     */
    public static function get_all_privileges(string $guid) : array
    {
        return array_merge(self::get_content_privileges($guid), self::get_self_privileges($guid));
    }

    /**
     * List all privileges assigned an object unfiltered.
     *
     * @return midcom_core_privilege[]
     */
    private static function _get_privileges(string $guid, string $type) : array
    {
        static $cache = [];

        $cache_key = $type . '::' . $guid;

        if (!array_key_exists($cache_key, $cache)) {
            $return = midcom::get()->cache->memcache->get('ACL', $cache_key);

            if (!is_array($return)) {
                // Didn't get privileges from cache, get them from DB
                $return = self::_query_privileges($guid, $type);
                midcom::get()->cache->memcache->put('ACL', $cache_key, $return);
            }

            $cache[$cache_key] = $return;
        }

        return $cache[$cache_key];
    }

    /**
     * Query the database for privileges and construct all necessary objects out of it.
     *
     * @param string $type SELF or CONTENT
     * @return midcom_core_privilege[]
     */
    protected static function _query_privileges(string $guid, string $type) : array
    {
        $result = [];

        $mc = new midgard_collector('midcom_core_privilege_db', 'objectguid', $guid);
        $mc->add_constraint('value', '<>', MIDCOM_PRIVILEGE_INHERIT);

        if ($type == 'CONTENT') {
            $mc->add_constraint('assignee', 'NOT IN', ['SELF', '']);
        } else {
            $mc->add_constraint('assignee', '=', 'SELF');
        }

        $mc->set_key_property('guid');
        $mc->add_value_property('id');
        $mc->add_value_property('privilegename');
        $mc->add_value_property('assignee');
        $mc->add_value_property('classname');
        $mc->add_value_property('value');
        $mc->execute();
        $privileges = $mc->list_keys();

        foreach (array_keys($privileges) as $privilege_guid) {
            $privilege = $mc->get($privilege_guid);
            $privilege['objectguid'] = $guid;
            $privilege['guid'] = $privilege_guid;
            $privilege_object = new static($privilege);
            $result[] = $privilege_object;
        }

        return $result;
    }

    /**
     * Retrieve a single given privilege at a content object, identified by
     * the combination of assignee and privilege name.
     *
     * This call will return an object even if the privilege is set to INHERITED at
     * the given object (i.e. does not exist) for consistency reasons. Errors are
     * thrown for example on database inconsistencies.
     *
     * This function is for use in the authentication framework only.
     *
     * @param string $classname The optional classname required only for class-limited SELF privileges.
     */
    public static function get_privilege(midcom_core_dbaobject $object, string $name, $assignee, string $classname = '') : midcom_core_privilege
    {
        $qb = new midgard_query_builder('midcom_core_privilege_db');
        $qb->add_constraint('objectguid', '=', $object->guid);
        $qb->add_constraint('privilegename', '=', $name);
        $qb->add_constraint('assignee', '=', $assignee);
        $qb->add_constraint('classname', '=', $classname);
        $result = $qb->execute();

        if (empty($result)) {
            // No such privilege stored, return non-persistent one
            $privilege = new self;
            $privilege->set_object($object);
            $privilege->set_assignee($assignee);
            $privilege->privilegename = $name;
            $privilege->classname = $classname;
            $privilege->value = MIDCOM_PRIVILEGE_INHERIT;
            return $privilege;
        }
        if (count($result) > 1) {
            debug_add('A DB inconsistency has been detected. There is more than one record for privilege specified. Deleting all excess records after the first one!',
                MIDCOM_LOG_ERROR);
            debug_print_r('Content Object:', $object);
            debug_add("Privilege {$name} for assignee {$assignee} with classname {$classname} was queried.", MIDCOM_LOG_INFO);
            debug_print_r('Resultset was:', $result);
            midcom::get()->auth->request_sudo('midcom.core');
            while (count($result) > 1) {
                $privilege = array_pop($result);
                $privilege->purge();
            }
            midcom::get()->auth->drop_sudo();
        }

        return new midcom_core_privilege($result[0]);
    }

    private function _load($src)
    {
        if ($src instanceof midcom_core_privilege_db) {
            // Got a privilege object as argument, use that
            $this->__guid = $src->guid;
            $this->__privilege_object = $src;
        } elseif (is_string($src) && mgd_is_guid($src)) {
            $this->__guid = $src;
            $this->__privilege_object = new midcom_core_privilege_db($src);
        } else {
            // Have a nonpersistent privilege
            $this->__privilege_object = new midcom_core_privilege_db();
        }
    }

    private function _sync_to_db_object()
    {
        if (!$this->__privilege_object) {
            $this->_load($this->guid);
        }
        $this->__privilege_object->objectguid = $this->objectguid;
        $this->__privilege_object->privilegename = $this->privilegename;
        $this->__privilege_object->assignee = $this->assignee;
        $this->__privilege_object->classname = $this->classname;
        $this->__privilege_object->value = $this->value;
    }

    private function _sync_from_db_object()
    {
        $this->objectguid = $this->__privilege_object->objectguid;
        $this->privilegename = $this->__privilege_object->privilegename;
        $this->assignee = $this->__privilege_object->assignee;
        $this->classname = $this->__privilege_object->classname;
        $this->value = $this->__privilege_object->value;
        $this->guid = $this->__privilege_object->guid;
    }

    /**
     * Store the privilege. This will validate it first and then either
     * update an existing privilege record, or create a new one, depending on the
     * DB state.
     */
    public function store() : bool
    {
        if (!$this->validate()) {
            debug_add('This privilege failed to validate, rejecting it, see the debug log for details.', MIDCOM_LOG_WARN);
            $this->__cached_object = null;
            debug_print_r('Privilege dump (w/o cached object):', $this);
            return false;
        }

        $this->_sync_to_db_object();

        if ($this->value == MIDCOM_PRIVILEGE_INHERIT) {
            if ($this->__guid) {
                // Already a persistent record, drop it.
                return $this->drop();
            }
            // This is a temporary object only, try to load the real object first. If it is not found,
            // exit silently, as this is the desired final state.
            $object = $this->get_object();
            $privilege = self::get_privilege($object, $this->privilegename, $this->assignee, $this->classname);
            if (!empty($privilege->__guid)) {
                if (!$privilege->drop()) {
                    return false;
                }
                $this->_invalidate_cache();
            }
            return true;
        }

        if ($this->__guid) {
            if (!$this->__privilege_object->update()) {
                return false;
            }
            $this->_invalidate_cache();
            return true;
        }

        $object = $this->get_object();
        $privilege = self::get_privilege($object, $this->privilegename, $this->assignee, $this->classname);
        if (!empty($privilege->__guid)) {
            $privilege->value = $this->value;
            if (!$privilege->store()) {
                debug_add('Update of the existing privilege failed.', MIDCOM_LOG_WARN);
                return false;
            }
            $this->__guid = $privilege->__guid;
            $this->objectguid = $privilege->objectguid;
            $this->privilegename = $privilege->privilegename;
            $this->assignee = $privilege->assignee;
            $this->classname = $privilege->classname;
            $this->value = $privilege->value;

            $this->_invalidate_cache();
            return true;
        }

        if (!$this->__privilege_object->create()) {
            debug_add('Creating new privilege failed: ' . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
            return false;
        }
        $this->__guid = $this->__privilege_object->guid;
        $this->_invalidate_cache();
        return true;
    }

    /**
     * Invalidate the memcache after I/O operations
     */
    private function _invalidate_cache()
    {
        midcom::get()->cache->invalidate($this->objectguid);
    }

    /**
     * Drop the privilege. If we are a known DB record, we delete us, otherwise
     * we return silently.
     */
    public function drop() : bool
    {
        $this->_sync_to_db_object();

        if (!$this->__guid) {
            debug_add('We are not stored, GUID is empty. Ignoring silently.');
            return true;
        }

        if (!$this->validate()) {
            debug_add('This privilege failed to validate, rejecting to drop it, see the debug log for details.', MIDCOM_LOG_WARN);
            debug_print_r('Privilege dump:', $this);
            return false;
        }

        if (!$this->__privilege_object->guid) {
            // We created this via collector, instantiate a new one
            $privilege = new midcom_core_privilege($this->__guid);
            return $privilege->drop();
        }

        if (!$this->__privilege_object->purge()) {
            debug_add('Failed to delete privilege record, aborting. Error: ' . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            return false;
        }

        debug_add("Deleted privilege record {$this->__guid} ({$this->__privilege_object->objectguid} {$this->__privilege_object->privilegename} {$this->__privilege_object->assignee} {$this->__privilege_object->value}");

        $this->_invalidate_cache();
        $this->value = MIDCOM_PRIVILEGE_INHERIT;

        return true;
    }
}
