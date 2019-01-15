<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Request;

/**
 * Main Authentication/Authorization service class, it provides means to authenticate
 * users and to check for permissions.
 *
 * <b>Authentication</b>
 *
 * Whenever the system successfully creates a new login session (during auth service startup),
 * it checks whether the key <i>midcom_services_auth_login_success_url</i> is present in the HTTP
 * Request data. If this is the case, it relocates to the URL given in it. This member isn't set
 * by default in the MidCOM core, it is intended for custom authentication forms. The MidCOM
 * relocate function is used to for relocation, thus you can take full advantage of the
 * convenience functions in there. See midcom_application::relocate() for details.
 *
 * <b>Checking Privileges</b>
 *
 * This class offers various methods to verify the privilege state of a user, all of them prefixed
 * with can_* for privileges and is_* for membership checks.
 *
 * Each function is available in a simple check version, which returns true or false, and a
 * require_* prefixed variant, which has no return value. The require variants of these calls
 * instead check if the given condition is met, if yes, they return silently, otherwise they
 * throw an access denied error.
 *
 * @todo Fully document authentication.
 * @package midcom.services
 */
class midcom_services_auth
{
    /**
     * The currently authenticated user or null in case of anonymous access.
     * It is to be considered read-only.
     *
     * @var midcom_core_user
     */
    public $user;

    /**
     * Admin user level state. This is true if the currently authenticated user is an
     * Midgard Administrator, false otherwise.
     *
     * This effectively maps to midcom_connection::is_admin(); but it is suggested to use the auth class
     * for consistency reasons nevertheless.
     *
     * @var boolean
     */
    public $admin = false;

    /**
     * The ACL management system.
     *
     * @var midcom_services_auth_acl
     */
    public $acl;

    /**
     * Internal cache of all loaded groups, indexed by their identifiers.
     *
     * @var Array
     */
    private $_group_cache = [];

    /**
     * Internal cache of all loaded users, indexed by their identifiers.
     *
     * @var Array
     */
    private $_user_cache = [];

    /**
     * This flag indicates if sudo mode is active during execution. This will only be the
     * case if the sudo system actually grants this privileges, and only until components
     * release the rights again. This does override the full access control system at this time
     * and essentially give you full admin privileges (though this might change in the future).
     *
     * Note, that this is no boolean but an int, otherwise it would be impossible to trace nested
     * sudo invocations, which are quite possible with multiple components calling each others
     * callback. A value of 0 indicates that sudo is inactive. A value greater than zero indicates
     * sudo mode is active, with the count being equal to the depth of the sudo callers.
     *
     * It is thus still safely possible to evaluate this member in a boolean context to check
     * for an enabled sudo mode.
     *
     * @var int
     * @see request_sudo()
     * @see drop_sudo()
     */
    private $_component_sudo = 0;

    /**
     * The authentication backend we should use by default.
     *
     * @var midcom_services_auth_backend
     */
    private $backend;

    /**
     * The authentication frontend we should use by default.
     *
     * @var midcom_services_auth_frontend
     */
    private $frontend;

    /**
     * Initialize the service:
     *
     * - Start up the login session service
     * - Load the core privileges.
     * - Initialize to the Midgard Authentication, then synchronize with the auth
     *   drivers' currently authenticated user overriding the Midgard Auth if
     *   necessary.
     */
    public function __construct()
    {
        $this->acl = new midcom_services_auth_acl($this);

        // Initialize from midgard
        if (   midcom_connection::get_user()
            && $user = $this->get_user(midcom_connection::get_user())) {
            $this->set_user($user);
        }

        $this->_prepare_authentication_drivers();
    }

    /**
     * Checks if the current authentication fronted has new credentials
     * ready. If yes, it processes the login accordingly. Otherwise look for existing session
     *
     * @param Request $request The request object
     */
    public function check_for_login_session(Request $request)
    {
        // Try to start up a new session, this will authenticate as well.
        if ($credentials = $this->frontend->read_login_data($request)) {
            if (!$this->login($credentials['username'], $credentials['password'], $request->getClientIp())) {
                return;
            }
            debug_add('Authentication was successful, we have a new login session now. Updating timestamps');

            $person_class = midcom::get()->config->get('person_class');
            $person = new $person_class($this->user->guid);
            if (   midcom::get()->config->get('auth_save_prev_login')
                && $person->get_parameter('midcom', 'last_login')) {
                $person->set_parameter('midcom', 'prev_login', $person->get_parameter('midcom', 'last_login'));
            }

            $person->set_parameter('midcom', 'last_login', time());

            if (!$person->get_parameter('midcom', 'first_login')) {
                $person->set_parameter('midcom', 'first_login', time());
            }

            // Now we check whether there is a success-relocate URL given somewhere.
            if ($request->get('midcom_services_auth_login_success_url')) {
                midcom::get()->relocate($request->get('midcom_services_auth_login_success_url'));
                // This will exit.
            }
        }
        // No new login detected, so we check if there is a running session.
        elseif ($user = $this->backend->check_for_active_login_session($request)) {
            $this->set_user($user);
        }
    }

    /**
     * @param midcom_core_user $user
     */
    private function set_user(midcom_core_user $user)
    {
        $this->user = $user;
        $this->admin = $user->is_admin();
    }

    /**
     * Internal startup helper, loads all configured authentication drivers.
     */
    private function _prepare_authentication_drivers()
    {
        $classname = midcom::get()->config->get('auth_backend');
        if (strpos($classname, "_") === false) {
            $classname = 'midcom_services_auth_backend_' . $classname;
        }
        $this->backend = new $classname($this);

        $classname = midcom::get()->config->get('auth_frontend');
        if (strpos($classname, "_") === false) {
            $classname = 'midcom_services_auth_frontend_' . $classname;
        }
        $this->frontend = new $classname();
    }

    /**
     * Checks whether a user has a certain privilege on the given content object.
     * Works on the currently authenticated user by default, but can take another
     * user as an optional argument.
     *
     * @param string $privilege The privilege to check for
     * @param MidgardObject $content_object A Midgard Content Object
     * @param midcom_core_user $user The user against which to check the privilege, defaults to the currently authenticated user.
     *     You may specify "EVERYONE" instead of an object to check what an anonymous user can do.
     * @return boolean True if the privilege has been granted, false otherwise.
     */
    public function can_do($privilege, $content_object, $user = null)
    {
        if (!is_object($content_object)) {
            return false;
        }

        if ($this->is_admin($user)) {
            // Administrators always have access.
            return true;
        }

        $user_id = $this->acl->get_user_id($user);

        //if we're handed the correct object type, we use its class right away
        if (midcom::get()->dbclassloader->is_midcom_db_object($content_object)) {
            $content_object_class = get_class($content_object);
        }
        //otherwise, we assume (hope) that it's a midgard object
        else {
            $content_object_class = midcom::get()->dbclassloader->get_midcom_class_name_for_mgdschema_object($content_object);
        }

        return $this->acl->can_do_byguid($privilege, $content_object->guid, $content_object_class, $user_id);
    }

    private function is_admin($user)
    {
        if ($user === null) {
            return $this->user && $this->admin;
        }
        if (is_a($user, midcom_core_user::class)) {
            return $user->is_admin();
        }
        return false;
    }

    /**
     * Checks, whether the given user have the privilege assigned to him in general.
     * Be aware, that this does not take any permissions overridden by content objects
     * into account. Whenever possible, you should user the can_do() variant of this
     * call therefore. can_user_do is only of interest in cases where you do not have
     * any content object available, for example when creating root topics.
     *
     * @param string $privilege The privilege to check for
     * @param midcom_core_user $user The user against which to check the privilege, defaults to the currently authenticated user,
     *     you may specify 'EVERYONE' here to check what an anonymous user can do.
     * @param string $class Optional parameter to set if the check should take type specific permissions into account. The class must be default constructible.
     * @return boolean True if the privilege has been granted, false otherwise.
     */
    public function can_user_do($privilege, $user = null, $class = null)
    {
        if ($this->is_admin($user)) {
            // Administrators always have access.
            return true;
        }
        if ($this->_component_sudo) {
            return true;
        }
        if ($user === null) {
            $user =& $this->user;
        }

        if ($user == 'EVERYONE') {
            $user = null;
        }

        return $this->acl->can_do_byclass($privilege, $user, $class);
    }

    /**
     * Request superuser privileges for the domain passed.
     *
     * STUB IMPLEMENTATION ONLY, WILL ALWAYS GRANT SUDO.
     *
     * You have to call midcom_services_auth::drop_sudo() as soon as you no longer
     * need the elevated privileges, which will reset the authentication data to the
     * initial credentials.
     *
     * @param string $domain The domain to request sudo for. This is a component name.
     * @return boolean True if admin privileges were granted, false otherwise.
     */
    public function request_sudo($domain = null)
    {
        if (!midcom::get()->config->get('auth_allow_sudo')) {
            debug_add("SUDO is not allowed on this website.", MIDCOM_LOG_ERROR);
            return false;
        }

        if ($domain === null) {
            $domain = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_COMPONENT);
            debug_add("Domain was not supplied, falling back to '{$domain}' which we got from the current component context.");
        }

        if ($domain == '') {
            debug_add("SUDO request for an empty domain, this should not happen. Denying sudo.", MIDCOM_LOG_INFO);
            return false;
        }

        $this->_component_sudo++;

        debug_add("Entered SUDO mode for domain {$domain}.", MIDCOM_LOG_INFO);

        return true;
    }

    /**
     * Drops previously acquired superuser privileges.
     *
     * @see request_sudo()
     */
    public function drop_sudo()
    {
        if ($this->_component_sudo > 0) {
            debug_add('Leaving SUDO mode.');
            $this->_component_sudo--;
        } else {
            debug_add('Requested to leave SUDO mode, but sudo was already disabled. Ignoring request.', MIDCOM_LOG_INFO);
        }
    }

    public function is_component_sudo()
    {
        return $this->_component_sudo > 0;
    }

    /**
     * Check, whether a user is member of a given group. By default, the query is run
     * against the currently authenticated user.
     *
     * It always returns true for administrative users.
     *
     * @param mixed $group Group to check against, this can be either a midcom_core_group object or a group string identifier.
     * @param midcom_core_user $user The user which should be checked, defaults to the current user.
     * @return boolean Indicating membership state.
     */
    public function is_group_member($group, $user = null)
    {
        if ($this->is_admin($user)) {
            // Administrators always have access.
            return true;
        }
        // Default parameter
        if ($user === null) {
            if ($this->user === null) {
                // not authenticated
                return false;
            }
            $user = $this->user;
        }

        return $user->is_in_group($group);
    }

    /**
     * Returns true if there is an authenticated user, false otherwise.
     *
     * @return boolean True if there is a user logged in.
     */
    public function is_valid_user()
    {
        return $this->user !== null;
    }

    /**
     * Validates that the current user has the given privilege granted on the
     * content object passed to the function.
     *
     * If this is not the case, an Access Denied error is generated, the message
     * defaulting to the string 'access denied: privilege %s not granted' of the
     * MidCOM main L10n table.
     *
     * The check is always done against the currently authenticated user. If the
     * check is successful, the function returns silently.
     *
     * @param string $privilege The privilege to check for
     * @param MidgardObject $content_object A Midgard Content Object
     * @param string $message The message to show if the privilege has been denied.
     */
    public function require_do($privilege, $content_object, $message = null)
    {
        if (!$this->can_do($privilege, $content_object)) {
            throw $this->access_denied($message, 'privilege %s not granted', $privilege);
        }
    }

    /**
     * Validates, whether the given user have the privilege assigned to him in general.
     * Be aware, that this does not take any permissions overridden by content objects
     * into account. Whenever possible, you should user the require_do() variant of this
     * call therefore. require_user_do is only of interest in cases where you do not have
     * any content object available, for example when creating root topics.
     *
     * If this is not the case, an Access Denied error is generated, the message
     * defaulting to the string 'access denied: privilege %s not granted' of the
     * MidCOM main L10n table.
     *
     * The check is always done against the currently authenticated user. If the
     * check is successful, the function returns silently.
     *
     * @param string $privilege The privilege to check for
     * @param string $message The message to show if the privilege has been denied.
     * @param string $class Optional parameter to set if the check should take type specific permissions into account. The class must be default constructible.
     */
    public function require_user_do($privilege, $message = null, $class = null)
    {
        if (!$this->can_user_do($privilege, null, $class)) {
            throw $this->access_denied($message, 'privilege %s not granted', $privilege);
        }
    }

    /**
     * Validates that the current user is a member of the given group.
     *
     * If this is not the case, an Access Denied error is generated, the message
     * defaulting to the string 'access denied: user is not member of the group %s' of the
     * MidCOM main L10n table.
     *
     * The check is always done against the currently authenticated user. If the
     * check is successful, the function returns silently.
     *
     * @param mixed $group Group to check against, this can be either a midcom_core_group object or a group string identifier.
     * @param string $message The message to show if the user is not member of the given group.
     */
    function require_group_member($group, $message = null)
    {
        if (!$this->is_group_member($group)) {
            if (is_object($group)) {
                $group = $group->name;
            }
            throw $this->access_denied($message, 'user is not member of the group %s', $group);
        }
    }

    /**
     * Validates that we currently have admin level privileges, which can either
     * come from the current user, or from the sudo service.
     *
     * If the check is successful, the function returns silently.
     *
     * @param string $message The message to show if the admin level privileges are missing.
     */
    public function require_admin_user($message = null)
    {
        if (!$this->admin && !$this->_component_sudo) {
            throw $this->access_denied($message, 'admin level privileges required');
        }
    }

    private function access_denied($message, $fallback, $data = null)
    {
        if ($message === null) {
            $message = midcom::get()->i18n->get_string('access denied: ' . $fallback, 'midcom');
            if ($data !== null) {
                $message = sprintf($message, $data);
            }
        }
        debug_print_function_stack("access_denied was called from here:");
        return new midcom_error_forbidden($message);
    }

    /**
     * Require either a configured IP address or admin credentials
     *
     * @param string $domain Domain for IP sudo
     * @throws midcom_error In case request_sudo fails
     * @return boolean True if IP sudo is active, false otherwise
     */
    public function require_admin_or_ip($domain)
    {
        $ips = midcom::get()->config->get('indexer_reindex_allowed_ips');
        if (   $ips
            && in_array($_SERVER['REMOTE_ADDR'], $ips)) {
            if (!$this->request_sudo($domain)) {
                throw new midcom_error('Failed to acquire SUDO rights. Aborting.');
            }
            return true;
        }

        // Require user to Basic-authenticate for security reasons
        $this->require_valid_user('basic');
        $this->require_admin_user();
        return false;
    }

    /**
     * Validates that there is an authenticated user.
     *
     * If this is not the case, the regular login page is shown automatically, see
     * show_login_page() for details.
     *
     * If the check is successful, the function returns silently.
     *
     * @param string $method Preferred authentication method: form or basic
     */
    public function require_valid_user($method = 'form')
    {
        if (!$this->is_valid_user()) {
            debug_print_function_stack("require_valid_user called at this level");
            if ($method == 'basic') {
                $this->_http_basic_auth();
            } else {
                $this->show_login_page();
            }
            // This will exit.
        }
    }

    /**
     * Handles HTTP Basic authentication
     */
    private function _http_basic_auth()
    {
        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            midcom::get()->header("WWW-Authenticate: Basic realm=\"Midgard\"");
            midcom::get()->header('HTTP/1.0 401 Unauthorized');
            // TODO: more fancy 401 output ?
            echo "<h1>Authorization required</h1>\n";
            midcom::get()->finish();
        } elseif ($user = $this->backend->authenticate($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
            $this->set_user($user);
        } else {
            // Wrong password: Recurse until auth ok or user gives up
            unset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
            $this->_http_basic_auth();
        }
    }

    /**
     * Resolve any assignee identifier known by the system into an appropriate user/group object.
     *
     * @param string $id A valid user or group identifier usable as assignee (e.g. the $id member
     *     of any midcom_core_user or midcom_core_group object).
     * @return object|false corresponding object or false on failure.
     */
    public function get_assignee($id)
    {
        $parts = explode(':', $id);

        if ($parts[0] == 'user') {
            return $this->get_user($id);
        }
        if ($parts[0] == 'group') {
            return $this->get_group($id);
        }
        debug_add("The identifier {$id} cannot be resolved into an assignee, it cannot be mapped to a type.", MIDCOM_LOG_WARN);

        return null;
    }

    /**
     * This is a wrapper for get_user, which allows user retrieval by its name.
     * If the username is unknown, false is returned.
     *
     * @param string $name The name of the user to look up.
     * @return midcom_core_user|false The user object matching the username, or false if the username is unknown.
     */
    public function get_user_by_name($name)
    {
        $mc = new midgard_collector('midgard_user', 'login', $name);
        $mc->set_key_property('person');
        $mc->add_constraint('authtype', '=', midcom::get()->config->get('auth_type'));
        $mc->execute();
        $keys = $mc->list_keys();
        if (count($keys) != 1) {
            return false;
        }

        $person_class = midcom::get()->config->get('person_class');
        $person = new $person_class(key($keys));

        return $this->get_user($person);
    }

    /**
     * This is a wrapper for get_group, which allows Midgard Group retrieval by its name.
     * If the group name is unknown, false is returned.
     *
     * In the case that more than one group matches the given name, the first one is returned.
     * Note, that this should not happen as midgard group names should be unique according to the specs.
     *
     * @param string $name The name of the group to look up.
     * @return midcom_core_group|false The group object matching the name, or false if the group name is unknown.
     */
    public function & get_midgard_group_by_name($name)
    {
        $qb = new midgard_query_builder('midgard_group');
        $qb->add_constraint('name', '=', $name);

        $result = $qb->execute();
        if (empty($result)) {
            $result = false;
            return $result;
        }
        $grp = $this->get_group($result[0]);
        return $grp;
    }

    /**
     * Load a user from the database and returns an object instance.
     *
     * @param mixed $id A valid identifier for a MidgardPerson: An existing midgard_person class
     *     or subclass thereof, a Person ID or GUID or a midcom_core_user identifier.
     * @return midcom_core_user|false The user object matching the identifier or false on failure.
     */
    public function get_user($id)
    {
        $param = $id;

        if (isset($param->id)) {
            $id = $param->id;
        } elseif (!is_string($id) && !is_int($id)) {
            debug_print_type('The passed argument was an object of an unsupported type:', $param, MIDCOM_LOG_WARN);
            debug_print_r('Complete object dump:', $param);
            return false;
        }
        if (!array_key_exists($id, $this->_user_cache)) {
            try {
                if (is_a($param, midcom_db_person::class)) {
                    $param = $param->__object;
                }
                $this->_user_cache[$id] = new midcom_core_user($param);
            } catch (midcom_error $e) {
                // Keep it silent while missing user object can mess here
                $this->_user_cache[$id] = false;
            }
        }

        return $this->_user_cache[$id];
    }

    /**
     * Returns a midcom_core_group instance. Valid arguments are either a valid group identifier
     * (group:...), any valid identifier for the midcom_core_group
     * constructor or a valid object of that type.
     *
     * @param mixed $id The identifier of the group as outlined above.
     * @return midcom_core_group|false A group object instance matching the identifier, or false on failure.
     */
    public function get_group($id)
    {
        $param = $id;

        if (isset($param->id)) {
            $id = $param->id;
        } elseif (!is_string($id) && !is_int($id)) {
            debug_print_type('The group identifier is of an unsupported type:', $param, MIDCOM_LOG_WARN);
            debug_print_r('Complete dump:', $param);
            return false;
        }

        if (!array_key_exists($id, $this->_group_cache)) {
            try {
                if (is_a($param, midcom_core_dbaobject::class)) {
                    $param = $param->__object;
                }
                $this->_group_cache[$id] = new midcom_core_group($param);
            } catch (midcom_error $e) {
                debug_add("Group with identifier {$id} could not be loaded: " . $e->getMessage(), MIDCOM_LOG_WARN);
                $this->_group_cache[$id] = false;
            }
        }
        return $this->_group_cache[$id];
    }

    /**
     * This call tells the backend to log in.
     */
    public function login($username, $password, $clientip = null)
    {
        if ($user = $this->backend->login($username, $password, $clientip)) {
            $this->set_user($user);
            return true;
        }
        debug_add('The login information for ' . $username . ' was invalid.', MIDCOM_LOG_WARN);
        return false;
    }

    public function trusted_login($username)
    {
        if (midcom::get()->config->get('auth_allow_trusted') !== true) {
            debug_add("Trusted logins are prohibited", MIDCOM_LOG_ERROR);
            return false;
        }

        if ($user = $this->backend->login($username, '', null, true)) {
            $this->set_user($user);
            return true;
        }
        return false;
    }

    /**
     * This call clears any authentication state
     */
    public function logout()
    {
        if ($this->user === null) {
            debug_add('The backend has no authenticated user set, so we should be fine');
        } else {
            $this->backend->logout($this->user);
            $this->user = null;
        }
        $this->admin = false;
    }

    /**
     * Render the main login form.
     * This only includes the form, no heading or whatsoever.
     *
     * It is recommended to call this function only as long as the headers are not yet sent (which
     * is usually given thanks to MidCOMs output buffering).
     *
     * What gets rendered depends on the authentication frontend, but will usually be some kind
     * of form.
     */
    public function show_login_form()
    {
        $this->frontend->show_login_form();
    }

    /**
     * Show a complete login page unconditionally and exit afterwards.
     * If the current style has an element called <i>midcom_services_auth_login_page</i>
     * it will be shown instead. The local scope will contain the two variables
     * $title and $login_warning. $title is the localized string 'login' from the main
     * MidCOM L10n DB, login_warning is empty unless there was a failed authentication
     * attempt, in which case it will have a localized warning message enclosed in a
     * paragraph with the ID 'login_warning'.
     */
    public function show_login_page()
    {
        $this->frontend->show_login_page();
    }

    public function show_access_denied($message)
    {
        $this->frontend->show_access_denied($message);
    }
}
