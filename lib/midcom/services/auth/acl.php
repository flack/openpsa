<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This class is responsible for ACL checks against classes and content objects.
 *
 * <b>Privilege definition</b>
 *
 * Privileges are represented by the class midcom_core_privilege and basically consist
 * of three parts: Name, Assignee and Value:
 *
 * The privilege name is a unique identifier for the privilege. The mRFC 15 defines the
 * syntax to be $component:$name, where $component is either the name of the component
 * or one of 'midgard' or 'midcom' for core privileges. Valid privilege names are for
 * example 'net.nehmer.static:do_something' or 'midgard:update'.
 *
 * The assignee is the entity to which the privilege applies, this can be one of several
 * things, depending on where the privilege is taken into effect, I'll explain this below
 * in more detail:
 *
 * On content objects (generally every object in the system used during 'normal operation'):
 *
 * - A Midgard User encapsulated by a midcom_core_user object.
 * - A Midgard Group encapsulated by a midcom_core_group object or subtype thereof.
 * - The magic assignee 'EVERYONE', which applies the privilege to every user unconditionally,
 *   even to unauthenticated users.
 * - The magic assignee 'USERS', which applies to all authenticated users.
 * - The magic assignee 'ANONYMOUS, which applies to all unauthenticated users.
 * - The magic assignee 'OWNER', which applies for all object owners.
 *
 * On users and groups during authentication (when building the basic privilege set for the user,
 * which applies generally):
 *
 * - The magic string 'SELF', which denotes that the privilege is set for the user in general for
 *   every content object. SELF privileges may be restricted to a class by using the classname
 *   property available at both midcom_core_privilege and various DBA interface functions.
 *
 * The value is one of MIDCOM_PRIVILEGE_ALLOW or MIDCOM_PRIVILEGE_DENY, which either grants or
 * revokes a privilege. Be aware, that unsetting a privilege does not set it to MIDCOM_PRIVILEGE_DENY,
 * but clears the entry completely, which means that the privilege value inherited from the parents
 * is now in effect.
 *
 * <b>How are privileges read and merged</b>
 *
 * First, you have to understand, that there are actually three distinct sources where a privilege
 * comes from: The systemwide defaults, the currently authenticated user and the content object
 * which is being operated on. We'll look into this distinction first, before we get on to the order
 * in which they are merged.
 *
 * <i>Systemwide default privileges</i>
 *
 * This is analogous to the MidCOM default configuration, they are taken into account globally to each
 * and every check whether a privilege is granted. Whenever a privilege is defined, there is also a
 * default value (either ALLOW or DENY) assigned to it. They serve as a basis for all privilege sets
 * and ensure that there is a value set for all privileges.
 *
 * These defaults are defined by the MidCOM core and the components respectively and are very restrictive,
 * basically granting read-only access to all non sensitive information.
 *
 * Currently, there is no way to influence these privileges unless you are a developer and writing new
 * components.
 *
 * <i>Class specific, systemwide default privileges (for magic assignees only)</i>
 *
 * Often you want to have a number of default privileges for certain classes in general. For regular
 * users/groups you can easily assign them to the corresponding users/groups, there is one special
 * case which cannot be covered there at this time: You cannot set defaults applicable for the magic
 * assignees EVERYONE, USERS and ANONYMOUS. This is normally only of interest for component authors,
 * which want to have some special privileges assigned for their objects, where the global defaults
 * do no longer suffice.
 *
 * These privileges are queried using a static callback of the DBA classes in question, see the following
 * example:
 *
 * <code>
 * public function get_class_magic_default_privileges()
 * {
 *     return Array (
 *         'EVERYONE' => [],
 *         'ANONYMOUS' => [],
 *         'USERS' => ['midcom:create' => MIDCOM_PRIVILEGE_ALLOW]
 *     );
 * }
 * </code>
 *
 * See also the documentation of the $_default_magic_class_privileges member for further details.
 *
 * <i>User / Group specific privileges</i>
 *
 * This kind of privileges are rights, assigned directly to a user. Similar to the systemwide defaults,
 * they too apply to any operation done by the user / group respectively throughout the system. The magic
 * assignee SELF is used to denote such privileges, which can obviously only be assigned to users or
 * groups. These privileges are loaded at the time of user authentication only.
 *
 * You should use these privileges carefully, due to their global nature. If you assign the privilege
 * midgard:delete to a user, this means that the user can now delete all objects he can read, unless
 * there are again restricting privileges set to content objects.
 *
 * To be more flexible in the control over the top level objects, you may add a classname which restricts
 * the validity of the privilege to a class and all of its descendants.
 *
 * <i>Content object privileges</i>
 *
 * This is the kind of privilege that will be used most often. They are associated with any content
 * object in the system, and are read on every access to a content object. As you can see in the
 * introduction, you have the most flexibility here.
 *
 * The basic idea is that you can assign privileges based on the combination of users/groups and
 * content objects. In other words, you can say the user x has the privilege midgard:update for
 * this object (and its descendants) only. This works with groups as well.
 *
 * The possible assignees here are either a user, a group or one of the magic assignees EVERYONE,
 * USERS or ANONYMOUS, as outlined above.
 *
 * Be aware, that persons and groups are treted as content objects when loaded from the database
 * in a tool like org.openpsa.user, as the groups are not used for authentication but for
 * regular site operation there. Therefore, the SELF privileges mentioned above are not taken into
 * account when determining the content object privileges!
 *
 * <i>Privilege merging</i>
 *
 * This is where we get to the guts of privilege system, as this is not trivial (but nevertheless
 * straight-forward I hope). The general idea is based on the scope of object a privilege applies:
 *
 * System default privileges obviously have the largest scope, they apply to everyone. The next
 * smaller scope are privileges which are assigned to groups in general, followed by privileges
 * assigned directly to a user.
 *
 * From this point on, the privileges of the content objects are next in line, starting at the
 * top-level objects again (for example a root topic). The smallest scope finally then has the
 * object that is being accessed itself.
 *
 * Let us visualize this a bit:
 *
 * <pre>
 * ^ larger scope     System default privileges
 * |                  Class specific magic assignee default privileges
 * |                  Root Midgard group
 * |                  ... more parent Midgard groups ...
 * |                  Direct Midgard group membership
 * |                  User
 * |                  SELF privileges limited to a class
 * |                  Root content object
 * |                  ... more parent objects ...
 * v smaller scope    Accessed content object
 * </pre>
 *
 * Privileges assigned to a specific user always override owner privileges; owner privileges are
 * calculated on a per-content-object bases, and are merged just before the final user privileges are
 * merged into the privilege set. It is of no importance from where you get ownership at that point.
 *
 * Implementation notes: Internally, MidCOM separates the "user privilege set" which is everything
 * down to the line User above, and the content object privileges, which constitutes the rest.
 * This separation has been done for performance reasons, as the user's privileges are loaded
 * immediately upon authentication of the user, and the privileges of the actual content objects
 * are merged into this set then. Normally, this should be of no importance for ACL users, but it
 * explains the more complex graph in the original mRFC.
 *
 * <b>Predefined Privileges</b>
 *
 * The MidCOM core defines a set of core privileges, which fall in two categories:
 *
 * <i>Midgard Core Privileges</i>
 *
 * These privileges are part of the MidCOM Database Abstraction layer (MidCOM DBA) and have been
 * originally proposed by me in a mail to the Midgard developers list. Unless otherwise noted,
 * all privileges are denied by default and no difference between owner and normal default privileges
 * is made.
 *
 * - <i>midgard:read</i> controls read access to the object, if denied, you cannot load the object
 *   from the database. This privilege is granted by default.
 * - <i>midgard:update</i> controls updating of objects. Be aware that you need to be able to read
 *   the object before updating it, it is granted by default only for owners.
 * - <i>midgard:delete</i> controls deletion of objects. Be aware that you need to be able to read
 *   the object before updating it, it is granted by default only for owners.
 * - <i>midgard:create</i> allows you to create new content objects as children on whatever content
 *   object that you have the create privilege for. This means that you can create an article if and only
 *   if you have create permission for either the parent article (if you create a so-called 'reply
 *   article') or the parent topic, it is granted by default only for owners.
 * - <i>midgard:parameters</i> allows the manipulation of parameters on the current object if and
 *   only if the user also has the midgard:update privilege on the object. This privileges is granted
 *   by default and covers the full set of parameter operations (create, update and delete).
 * - <i>midgard:attachments</i> is analogous to midgard:parameters but covers attachments instead
 *   and is also granted by default.
 * - <i>midgard:autoserve_attachment</i> controls whether an attachment may be autoserved using
 *   the midcom-serveattachmentguid handler. This is granted by default, allowing every attachment
 *   to be served using the default URL methods. Denying this right allows component authors to
 *   build more sophisticated access control restrictions to attachments.
 * - <i>midgard:privileges</i> allows the user to change the permissions on the objects they are
 *   granted for. You also need midgard:update and midgard:parameters to properly execute these
 *   operations.
 * - <i>midgard:owner</i> indicates that the user who has this privilege set is an owner of the
 *   given content object.
 *
 * <i>MidCOM Core Privileges</i>
 *
 * - <i>midcom:approve</i> grants the user the right to approve or unapprove objects.
 * - <i>midcom:component_config</i> grants the user access to configuration management system,
 *   it is granted by default only for owners.
 * - <i>midcom:isonline</i> is needed to see the online state of another user. It is not granted
 *   by default.
 *
 * <b>Assigning Privileges</b>
 *
 * See the documentation of the DBA layer for more information.
 *
 * @package midcom.services
 */
class midcom_services_auth_acl
{
    /**
     * @var midcom_services_auth
     */
    private $auth;

    /**
     * This is an internal flag used to override all regular permission checks with a sort-of
     * read-only privilege set. While internal_sudo is enabled, the system automatically
     * grants all privileges except midgard:create, midgard:update, midgard:delete and
     * midgard:privileges, which will always be denied. These checks go after the basic checks
     * for not authenticated users or admin level users.
     *
     * @var boolean
     */
    private $_internal_sudo = false;

    /**
     * Internal listing of all default privileges currently registered in the system. This
     * is a privilege name/value map.
     *
     * @var array
     */
    private static $_default_privileges = [];

    /**
     * Internal listing of all default owner privileges currently registered in the system.
     * All privileges not set in this list will be inherited. This is a privilege name/value
     * map.
     *
     * @var array
     */
    private static $_owner_default_privileges = [];

    /**
     * This listing contains all magic privileges assigned to the existing classes. It is a
     * multi-level array, example entry:
     *
     * <pre>
     * 'class_name' => Array
     * (
     *     'EVERYONE' => [],
     *     'ANONYMOUS' => [],
     *     'USERS' => Array
     *     (
     *         'midcom:create' => MIDCOM_PRIVILEGE_ALLOW,
     *         'midcom:update' => MIDCOM_PRIVILEGE_ALLOW
     *     ),
     * )
     * </pre>
     *
     * @var array
     */
    private static $_default_magic_class_privileges = [];

    /**
    * Internal cache of the content privileges of users on content objects, this is
    * an associative array using a combination of the user identifier and the object's
    * guid as index. The privileges for the anonymous user use the magic
    * EVERYONE as user identifier.
    *
    * This must not be merged with the class-wide privileges_cache, because otherwise
    * class_default_privileges for child objects might be overridden by parent default
    * privileges
    *
    * @var Array
    */
    private static $_content_privileges_cache = [];

    /**
     * Constructor.
     */
    public function __construct(midcom_services_auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Merges a new set of default privileges into the current set.
     * Existing keys will be silently overwritten.
     *
     * This is usually only called by the framework startup and the
     * component loader.
     *
     * If only a single default value is set (type integer), then this value is taken
     * for the default and the owner privilege is unset (meaning INHERIT). If two
     * values (type array of integers) is set, the first privilege value is used for
     * default, the second one for the owner privilege set.
     *
     * @param array $privileges An associative privilege_name => default_values listing.
     */
    public function register_default_privileges($privileges)
    {
        foreach ($privileges as $name => $values) {
            if (!is_array($values)) {
                $values = [$values, MIDCOM_PRIVILEGE_INHERIT];
            }

            self::$_default_privileges[$name] = $values[0];
            if ($values[1] != MIDCOM_PRIVILEGE_INHERIT) {
                self::$_owner_default_privileges[$name] = $values[1];
            }
        }
    }

    /**
     * Returns the system-wide basic privilege set.
     *
     * @return Array Privilege Name / Value map.
     */
    public function get_default_privileges()
    {
        return self::$_default_privileges;
    }

    /**
     * Returns the system-wide basic owner privilege set.
     *
     * @return Array Privilege Name / Value map.
     */
    public function get_owner_default_privileges()
    {
        return self::$_owner_default_privileges;
    }

    /**
     * Load and prepare the list of class magic privileges for usage.
     *
     * @param string $class The class name for which defaults should be loaded.
     * @param mixed $user The user to check
     */
    private function _get_class_magic_privileges($class, $user)
    {
        if (!array_key_exists($class, self::$_default_magic_class_privileges)) {
            $privs = [
                'EVERYONE' => [],
                'ANONYMOUS' => [],
                'USERS' => []
            ];

            if (method_exists($class, 'get_class_magic_default_privileges')) {
                $object = new $class();
                $privs = $object->get_class_magic_default_privileges();
            }

            self::$_default_magic_class_privileges[$class] = $privs;
        }
        $dmcp_user = $user === null ? 'ANONYMOUS' : 'USERS';
        return array_merge(
            self::$_default_magic_class_privileges[$class]['EVERYONE'],
            self::$_default_magic_class_privileges[$class][$dmcp_user]
        );
    }

    private function _get_user_per_class_privileges($classname, midcom_core_user $user)
    {
        static $cache = [];

        $cache_id = $user->id . '::' . $classname;

        if (!array_key_exists($cache_id, $cache)) {
            $cache[$cache_id] = [];
            $object = new $classname;

            foreach ($user->get_per_class_privileges() as $class => $privileges) {
                if (midcom::get()->dbfactory->is_a($object, $class)) {
                    $cache[$cache_id] = array_merge($cache[$cache_id], $privileges);
                }
            }
        }

        return $cache[$cache_id];
    }

    /**
     * Determine the user identifier for accessing the privilege cache. This is the passed user's
     * identifier with the current user and anonymous as fallback
     *
     * @param mixed $user The user to check for as string or object.
     * @return string The identifier
     */
    public function get_user_id($user = null)
    {
        $user_id = 'ANONYMOUS';

        // TODO: Clean if/else shorthands, make sure this works correctly for magic assignees as well
        if ($user === null) {
            if ($this->auth->user) {
                $user_id = $this->auth->user->id;
            }
        } elseif (is_string($user)) {
            if (mgd_is_guid($user) || is_numeric($user)) {
                $user_id = $this->auth->get_user($user)->id;
            } else {
                $user_id = $user;
            }
        } elseif (is_object($user)) {
            $user_id = $user->id;
        } else {
            $user_id = $user;
        }

        return $user_id;
    }

    /**
     * Validate whether a given privilege exists by its name. Essentially this checks
     * if a corresponding default privilege has been registered in the system.
     *
     * @todo This call should load the component associated to the privilege on demand.
     * @param string $name The name of the privilege to check.
     * @return boolean Indicating whether the privilege does exist.
     */
    public function privilege_exists($name)
    {
        return array_key_exists($name, self::$_default_privileges);
    }

    public function can_do_byclass($privilege, $user, $class)
    {
        if ($this->_internal_sudo) {
            debug_add('INTERNAL SUDO mode is enabled. Generic Read-Only mode set.');
            return $this->_can_do_internal_sudo($privilege);
        }

        $default_magic_class_privileges = [];
        $user_privileges = [];
        $user_per_class_privileges = [];

        if ($user !== null) {
            $user_privileges = $user->get_privileges();
        }

        if ($class !== null) {
            if (is_object($class)) {
                $class = get_class($class);
            } elseif (!class_exists($class)) {
                debug_add("can_user_do check to undefined class '{$class}'.", MIDCOM_LOG_ERROR);
                return false;
            }

            $default_magic_class_privileges = $this->_get_class_magic_privileges($class, $user);
            if ($user !== null) {
                $user_per_class_privileges = $this->_get_user_per_class_privileges($class, $user);
            }
        }

        $full_privileges = array_merge(
            self::$_default_privileges,
            $default_magic_class_privileges,
            $user_privileges,
            $user_per_class_privileges
        );

        // Check for Ownership:
        if ($full_privileges['midgard:owner'] == MIDCOM_PRIVILEGE_ALLOW) {
            $full_privileges = array_merge(
                $full_privileges,
                $this->get_owner_default_privileges()
            );
        }

        if (!array_key_exists($privilege, $full_privileges)) {
            debug_add("Warning, the privilege {$privilege} is unknown at this point. Assuming not granted privilege.");
            return false;
        }

        return $full_privileges[$privilege] == MIDCOM_PRIVILEGE_ALLOW;
    }

    /**
     * Checks whether a user has a certain privilege on the given (via guid and class) content object.
     * Works on the currently authenticated user by default, but can take another
     * user as an optional argument.
     *
     * @param string $privilege The privilege to check for
     * @param string $object_guid A Midgard GUID pointing to an object
     * @param string $object_class Class of the object in question
     * @param string $user_id The user against which to check the privilege, defaults to the currently authenticated user.
     *     You may specify "EVERYONE" instead of an object to check what an anonymous user can do.
     * @return boolean True if the privilege has been granted, false otherwise.
     */
    public function can_do_byguid($privilege, $object_guid, $object_class, $user_id)
    {
        if ($this->_internal_sudo) {
            return $this->_can_do_internal_sudo($privilege);
        }

        if ($this->auth->is_component_sudo()) {
            return true;
        }
        static $cache = [];

        $cache_prefix = "{$user_id}::{$object_guid}";
        $cache_key = $cache_prefix . "::{$privilege}";

        if (isset($cache[$cache_key])) {
            return $cache[$cache_key];
        }

        if ($this->_load_content_privilege($privilege, $object_guid, $object_class, $user_id)) {
            $cache[$cache_key] = self::$_content_privileges_cache[$cache_prefix][$privilege];
            return $cache[$cache_key];
        }

        // user privileges
        if ($user = $this->auth->get_user($user_id)) {
            $user_per_class_privileges = $this->_get_user_per_class_privileges($object_class, $user);

            if (array_key_exists($privilege, $user_per_class_privileges)) {
                $cache[$cache_key] = ($user_per_class_privileges[$privilege] == MIDCOM_PRIVILEGE_ALLOW);
                return $cache[$cache_key];
            }

            $user_privileges = $user->get_privileges();

            if (array_key_exists($privilege, $user_privileges)) {
                $cache[$cache_key] = ($user_privileges[$privilege] == MIDCOM_PRIVILEGE_ALLOW);
                return $cache[$cache_key];
            }
        }

        // default magic class privileges user
        $dmcp = $this->_get_class_magic_privileges($object_class, $this->auth->user);

        if (array_key_exists($privilege, $dmcp)) {
            $cache[$cache_key] = ($dmcp[$privilege] == MIDCOM_PRIVILEGE_ALLOW);
            return $cache[$cache_key];
        }

        if (array_key_exists($privilege, self::$_default_privileges)) {
            $cache[$cache_key] = (self::$_default_privileges[$privilege] == MIDCOM_PRIVILEGE_ALLOW);
            return $cache[$cache_key];
        }

        debug_add("The privilege {$privilege} is unknown at this point. Assuming not granted privilege.", MIDCOM_LOG_WARN);
        return false;
    }

    /**
     * Look up a specific content privilege and cache the result.
     *
     * @param string $privilegename The privilege to check for
     * @param string $guid A Midgard GUID pointing to an object
     * @param string $class DBA Class of the object in question
     * @param string $user_id The user against which to check the privilege, defaults to the currently authenticated user.
     * @return boolean True when privilege was found, otherwise false
     */
    private function _load_content_privilege($privilegename, $guid, $class, $user_id)
    {
        $cache_id = $user_id . '::' . $guid;

        if (!array_key_exists($cache_id, self::$_content_privileges_cache)) {
            self::$_content_privileges_cache[$cache_id] = [];
        }
        if (array_key_exists($privilegename, self::$_content_privileges_cache[$cache_id])) {
            return true;
        }

        $object_privileges = midcom_core_privilege::get_content_privileges($guid);

        $last_scope = -1;
        $content_privilege = null;

        foreach ($object_privileges as $privilege) {
            if ($privilege->privilegename == $privilegename) {
                $scope = $privilege->get_scope();
                if ($scope > $last_scope && $privilege->does_privilege_apply($user_id)) {
                    $last_scope = $scope;
                    $content_privilege = $privilege;
                }
            }
        }

        //owner privileges override everything but person privileges, so we have to cross-check those here
        if (   $privilegename != 'midgard:owner'
            && $last_scope < MIDCOM_PRIVILEGE_SCOPE_OWNER) {
            $owner_privileges = $this->get_owner_default_privileges();
            if (    array_key_exists($privilegename, $owner_privileges)
                 && $this->_load_content_privilege('midgard:owner', $guid, $class, $user_id)
                 && self::$_content_privileges_cache[$cache_id]['midgard:owner']) {
                self::$_content_privileges_cache[$cache_id][$privilegename] = ($owner_privileges[$privilegename] == MIDCOM_PRIVILEGE_ALLOW);
                return true;
            }
        }

        if ($content_privilege !== null) {
            self::$_content_privileges_cache[$cache_id][$privilegename] = ($content_privilege->value == MIDCOM_PRIVILEGE_ALLOW);
            return true;
        }

        //if nothing was found, we try to recurse to parent
        list ($parent_guid, $parent_class) = $this->get_parent_data($guid, $class);

        if (   $parent_guid == $guid
            || !mgd_is_guid($parent_guid)) {
            return false;
        }

        $parent_cache_id = $user_id . '::' . $parent_guid;
        if ($this->_load_content_privilege($privilegename, $parent_guid, $parent_class, $user_id)) {
            self::$_content_privileges_cache[$cache_id][$privilegename] = self::$_content_privileges_cache[$parent_cache_id][$privilegename];
            return true;
        }

        return false;
    }

    private function get_parent_data($guid, $class)
    {
        // ==> into SUDO
        $previous_sudo = $this->_internal_sudo;
        $this->_internal_sudo = true;
        $parent_data = midcom::get()->dbfactory->get_parent_data($guid, $class);
        $this->_internal_sudo = $previous_sudo;
        // <== out of SUDO
        return [current($parent_data), key($parent_data)];
    }

    /**
     * This internal helper checks if a privilege is available during internal
     * sudo mode, as outlined in the corresponding variable.
     *
     * @param string $privilege The privilege to check for
     * @return boolean True if the privilege has been granted, false otherwise.
     * @see $_internal_sudo
     */
    private function _can_do_internal_sudo($privilege)
    {
        return !in_array($privilege, ['midgard:create', 'midgard:update', 'midgard:delete', 'midgard:privileges']);
    }
}
