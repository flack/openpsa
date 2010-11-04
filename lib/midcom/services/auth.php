<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: auth.php 26711 2010-10-21 21:11:09Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

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
     * @access public
     */
    var $user = null;

    /**
     * Admin user level state. This is true if the currently authenticated user is an
     * Midgard Administrator, false otherwise.
     *
     * This effectively maps to $_MIDGARD['admin']; but it is suggested to use the auth class
     * for consistency reasons nevertheless.
     *
     * @var boolean
     * @access public
     */
    var $admin = false;

    /**
     * This is a reference to the login session management system.
     *
     * @var midcom_services_auth_sessionmgr
     * @access public
     */
    var $sessionmgr = null;

    /**
     * This is a reference to the ACL management system.
     *
     * @var midcom_services_auth_acl
     * @access public
     */
    var $acl = null;

    /**
     * Internal listing of all known virtual groups, populated from the
     * MidCOM Virtual Groups registry. It indexes virtual group identifiers suitable for get_group
     * with their clear-text names.
     *
     * @var array
     * @access private
     */
    var $_vgroups = null;

    /**
     * Internal cache of all loaded groups, indexed by their identifiers.
     *
     * @var Array
     * @access private
     */
    var $_group_cache = Array();

    /**
     * Internal cache of all loaded users, indexed by their identifiers.
     *
     * @var Array
     * @access private
     */
    var $_user_cache = Array();

    /**
     * This flag indicates if sudo mode is active during execution. This will only be the
     * case if the sudo system actually grants this privileges, and only until components
     * release the rights again. This does override the full access control system at this time
     * and essentially give you full admin privileges (though this might change in the future).
     *
     * Note, that this is no boolean but an int, otherwise it would be impossible to trace nested
     * sudo invocations, which are quite possible with multiple components calling each others
     * callback. A value of 0 indicates that sudo is inactive. A value greater then zero indicates
     * sudo mode is active, with the count being equal to the depth of the sudo callers.
     *
     * It is thus still safely possible to evaluate this member in a boolean context to check
     * for an enabled sudo mode.
     *
     * @var int
     * @access private
     * @see request_sudo()
     * @see drop_sudo()
     */
    var $_component_sudo = 0;

    /**
     * A reference to the authentication backend we should use by default.
     *
     * @var midcom_services_auth_backend
     * @access private
     */
    var $_auth_backend = null;

    /**
     * A reference to the authentication frontend we should use by default.
     *
     * @var midcom_services_auth_frontend
     * @access private
     */
    var $_auth_frontend = null;

    /**
     * Flag, which is set to true if the system encountered any new login credentials
     * during startup. If this is true, but no user is authenticated, login did fail.
     *
     * The variable is to be considered read-only.
     *
     * @var boolean
     * @access public
     */
    var $auth_credentials_found = false;

    /**
     * Initialize the service:
     *
     * - Start up the login session service
     * - Load the core privileges.
     * - Initialize to the Midgard Authentication, then synchronize with the auth
     *   drivers' currently authenticated user overriding the Midgard Auth if
     *   necessary.
     */
    function initialize()
    {
        $this->sessionmgr = new midcom_services_auth_sessionmgr($this);
        $this->acl = new midcom_services_auth_acl($this);


        // Midgard 8.09beta compatibility: ensure that Midgard's sitegroup ID is always int
        if (!is_integer($_MIDGARD['sitegroup']))
        {
            $_MIDGARD['sitegroup'] = (int) $_MIDGARD['sitegroup'];
        }

        $this->_initialize_user_from_midgard();
        $this->_prepare_authentication_drivers();

        if ($GLOBALS['midcom_config']['auth_drupal_enable'] == true)
        {
            // using drupal auth
            $this->_check_for_drupal_session();
        }
        else
        {
            // regular case
            if (! $this->_check_for_new_login_session())
            {
                // No new login detected, so we check if there is a running session.
                $this->_check_for_active_login_session();
            }
        }
    }

    /**
     * Internal startup helper, checks if there is drupal session active and syncs midcom auth
     * with it
     *
     * @return boolean Returns true, if authenticated, false if anonymous
     * @access private
     */
    private function _check_for_drupal_session()
    {
        $lib_starter = realpath(dirname(__FILE__).'/../../net/nemein/drupalauth/_load.php');
        if (!file_exists($lib_starter))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("net.nemein.drupalauth component is not found", MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        require_once $lib_starter;

        $drupal_user = net_nemein_drupalauth_api::getCurrentDrupalUser($this);

        if ($drupal_user === null)
        {
            // drupal is anonymous
            if ($this->_auth_backend->read_login_session())
            {
                // midcom is not anonymous. need to fix
                $this->drop_login_session();
            }

            return false;
        }
        else
        {
            // drupal is authenticated

            $need_to_auth = true;

            if ($this->_auth_backend->read_login_session())
            {
                // midcom is authenticated
                if ($this->_auth_backend->user->username != $drupal_user)
                {
                    // drupal has different user. fixing
                    $this->drop_login_session();
                }
                else
                {
                    // everything is looking good
                    $need_to_auth = false;
                }
            }

            if ($need_to_auth)
            {
                // midcom is not authenticated
                if (!$this->trusted_login($drupal_user))
                {
                    debug_push_class(__CLASS__, __FUNCTION__);
                    debug_add("Failed to authenticate '{$drupal_user}' username.", MIDCOM_LOG_ERROR);
                    debug_pop();
                    return false;
                }
            }
            else
            {
                if (!$this->sessionmgr->authenticate_session($this->_auth_backend->session_id))
                {
                    debug_push_class(__CLASS__, __FUNCTION__);
                    debug_add('Failed to re-authenticate a previous login session, not changing credentials.');
                    debug_pop();
                    return false;
                }
            }

            $this->_sync_user_with_backend();

            return true; // authenticated!
        }
    }

    /**
     * Internal startup helper, checks if the current authentication fronted has new credentials
     * ready. If yes, it processes the login accordingly.
     *
     * @return boolean Returns true, if a new login session was created, false if no credentials were found.
     * @access private
     */
    function _check_for_new_login_session()
    {
        $credentials = $this->_auth_frontend->read_authentication_data();

        if (! $credentials)
        {
            return false;
        }

        $this->auth_credentials_found = true;

        // Try to start up a new session, this will authenticate as well.
        if (! $this->_auth_backend->create_login_session($credentials['username'], $credentials['password']))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('The login information passed to the system was invalid.', MIDCOM_LOG_ERROR);
            debug_add("Username was {$credentials['username']}");
            // No password logging for security reasons.
            debug_pop();

            if (   isset($GLOBALS['midcom_config']['auth_failure_callback'])
                && !empty($GLOBALS['midcom_config']['auth_failure_callback'])
                && is_callable($GLOBALS['midcom_config']['auth_failure_callback']))
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_print_r('Calling auth failure callback: ', $GLOBALS['midcom_config']['auth_failure_callback'], MIDCOM_LOG_DEBUG);
                debug_pop();
                // Calling the failure function with the username as a parameter. No password sended to the user function for security reasons
                call_user_func($GLOBALS['midcom_config']['auth_failure_callback'], $credentials['username']);
            }

            return false;
        }

        debug_push_class(__CLASS__, __FUNCTION__);
        debug_add('Authentication was successful, we have a new login session now. Updating timestamps');
        debug_pop();

        $this->_sync_user_with_backend();

        if (   $GLOBALS['midcom_config']['auth_save_prev_login']
            && $this->user->_storage->parameter('midcom', 'last_login'))
        {
            $this->user->_storage->parameter('midcom', 'prev_login', $this->user->_storage->parameter('midcom', 'last_login'));
        }

        $this->user->_storage->parameter('midcom', 'last_login', time());

        if (! $this->user->_storage->parameter('midcom', 'first_login'))
        {
            $this->user->_storage->parameter('midcom', 'first_login', time());
        }

        if (   isset($GLOBALS['midcom_config']['auth_success_callback'])
            && !empty($GLOBALS['midcom_config']['auth_success_callback'])
            && is_callable($GLOBALS['midcom_config']['auth_success_callback']))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_print_r('Calling auth success callback:', $GLOBALS['midcom_config']['auth_success_callback'], MIDCOM_LOG_DEBUG);
            debug_pop();
            // Calling the success function. No parameters, because authenticated user is stored in $_MIDGARD['user']
            call_user_func($GLOBALS['midcom_config']['auth_success_callback']);
        }

        // There was form data sent before authentication was re-required
        if (   isset($_POST['restore_form_data'])
            && isset($_POST['restored_form_data']))
        {
            foreach ($_POST['restored_form_data'] as $key => $string)
            {
                $value = @unserialize(base64_decode($string));
                $_POST[$key] = $value;
                $_REQUEST[$key] = $value;
            }
        }

        // Now we check whether there is a success-relocate URL given somewhere.
        if (array_key_exists('midcom_services_auth_login_success_url', $_REQUEST))
        {
            if (isset($_MIDCOM))
            {
                $_MIDCOM->relocate($_REQUEST['midcom_services_auth_login_success_url']);
            }
            else
            {
                _midcom_header("Location: {$_REQUEST['midcom_services_auth_login_success_url']}");
                _midcom_stop_request();
            }
            // This will exit.
        }
        return true;
    }

    /**
     * Internal helper, synchronizes the main service class with the authentication state
     * of the authentication backend.
     */
    function _sync_user_with_backend()
    {
        $this->user =& $this->_auth_backend->user;
        // This check is a bit fuzzy but will work as long as MidgardAuth is in sync with
        // MidCOM auth.
        if (   $_MIDGARD['admin']
            || $_MIDGARD['root'])
        {
            $this->admin = true;
        }
        else
        {
            $this->admin = false;
        }
    }

    /**
     * Internal startup helper, checks the currently running authentication backend for
     * a running login session.
     *
     * @access private
     */
    function _check_for_active_login_session()
    {
        if (! $this->_auth_backend->read_login_session())
        {
            return;
        }

        if (! $this->sessionmgr->authenticate_session($this->_auth_backend->session_id))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('Failed to re-authenticate a previous login session, not changing credentials.');
            debug_pop();
            return;
        }

        $this->_sync_user_with_backend();
    }

    /**
     * Internal startup helper, synchronizes the authenticated user with the Midgard Authentication
     * for startup. This will be overridden by MidCOM Auth, but is there for compatibility reasons.
     *
     * @access private
     */
    function _initialize_user_from_midgard()
    {
        if ($_MIDGARD['user'])
        {
            $this->user = $this->get_user($_MIDGARD['user']);
            if (   $_MIDGARD['admin']
                || $_MIDGARD['root'])
            {
                $this->admin = true;
            }
        }
    }

    /**
     * Internal startup helper, loads all configured authentication drivers.
     *
     * @access private
     */
    function _prepare_authentication_drivers()
    {
        require_once (MIDCOM_ROOT . "/midcom/services/auth/backend/{$GLOBALS['midcom_config']['auth_backend']}.php");
        $classname = "midcom_services_auth_backend_{$GLOBALS['midcom_config']['auth_backend']}";
        $this->_auth_backend = new $classname($this);

        require_once (MIDCOM_ROOT . "/midcom/services/auth/frontend/{$GLOBALS['midcom_config']['auth_frontend']}.php");
        $classname = "midcom_services_auth_frontend_{$GLOBALS['midcom_config']['auth_frontend']}";
        $this->_auth_frontend = new $classname();
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
    function can_do($privilege, $content_object, $user = null)
    {
        if (!is_object($content_object))
        {
            return false;
        }

        if (   $privilege !== 'midgard:read'
            && $_MIDGARD['sitegroup'] !== 0
            && $content_object->sitegroup !== $_MIDGARD['sitegroup'])
        {
            return false;
        }

        // Prevent deleting from outside the language context
        if ($privilege === 'midgard:delete')
        {
            // Check the language context conditions
            // Hide the delete folder toolbar item if the language context conditions don't match
            if (!midcom_baseclasses_core_dbobject::delete_pre_multilang_checks($content_object))
            {
                return false;
            }
        }

        if (   is_null($user)
            && ! is_null($this->user)
            && $this->admin)
        {
            // Administrators always have access.
            return true;
        }

        $user_id = $this->acl->get_user_id($user);

        //if we're handed the correct object type, we use it's class right away
        if ($_MIDCOM->dbclassloader->is_midcom_db_object($content_object))
        {
            $content_object_class = get_class($content_object);
        }
        //otherwise, we assume (hope) that it's a midgard object
        else
        {
            $content_object_class = $_MIDCOM->dbclassloader->get_midcom_class_name_for_mgdschema_object($content_object);
        }

        return $this->acl->can_do_byguid($privilege, $content_object->guid, $content_object_class, $user_id);
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
     * @param string $component Component providing the class
     * @return boolean True if the privilege has been granted, false otherwise.
     */
    function can_user_do($privilege, $user = null, $class = null, $component = null)
    {
        if (is_null($user))
        {
            if ($this->admin)
            {
                // Administrators always have access.
                return true;
            }
            $user =& $this->user;
        }

        if ($this->_component_sudo)
        {
            return true;
        }

        if (   is_string($user)
            && $user == 'EVERYONE')
        {
            $user = null;
        }

        if (!is_null($user))
        {
            if (is_object($class))
            {
                $classname = get_class($class);
            }
            else
            {
                $classname = $class;
            }

            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Querying privilege {$privilege} for user {$user->id} to class {$classname}", MIDCOM_LOG_DEBUG);
            debug_pop();
        }

        debug_pop();
        return $this->acl->can_do_byclass($privilege, $user, $class, $component);
    }

    /**
     * Returns a full listing of all currently known privileges for a certain object/user
     * combination.
     *
     * The information is cached per object-guid during runtime, so that repeated checks
     * to the same object do not cause repeating checks. Be aware that this means, that
     * new privileges set are not guaranteed to take effect until the next request.
     *
     * @param MidgardObject &$content_object A Midgard Content Object
     * @param midcom_core_user $user The user against which to check the privilege, defaults to the currently authenticated user.
     *     You may specify "EVERYONE" instead of an object to check what an anonymous user can do.
     * @return Array Associative listing of all privileges and their value.
     */
    function get_privileges(&$content_object, $user = null)
    {
        $user_id = $this->acl->get_user_id($user);

        return $this->acl->get_privileges_byguid($content_object->guid, get_class($content_object), $user_id);
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
    function request_sudo ($domain = null)
    {
        debug_push_class(__CLASS__, __FUNCTION__);

        if (! $GLOBALS['midcom_config']['auth_allow_sudo'])
        {
            debug_add("SUDO is not allowed on this website.", MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }

        if (is_null($domain))
        {
            $domain = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_COMPONENT);
            debug_add("Domain was not supplied, falling back to '{$domain}' which we got from the current component context.");
        }

        if ($domain == '')
        {
            debug_add("SUDO request for an empty domain, this should not happen. Denying sudo.", MIDCOM_LOG_INFO);
            debug_pop();
            return false;
        }

        $this->_component_sudo++;

        debug_add("Entered SUDO mode for domain {$domain}.", MIDCOM_LOG_INFO);

        debug_pop();
        return true;
    }

    /**
     * Drops previously acquired superuser privileges.
     *
     * @see request_sudo()
     */
    function drop_sudo()
    {
        debug_push_class(__CLASS__, __FUNCTION__);

        if ($this->_component_sudo > 0)
        {
            debug_add('Leaving SUDO mode.');
            $this->_component_sudo--;
        }
        else
        {
            debug_add('Requested to leave SUDO mode, but sudo was already disabled. Ignoring request.', MIDCOM_LOG_INFO);
        }

        debug_pop();
    }

    /**
     * Check, whether a user is member of a given group. By default, the query is run
     * against the currently authenticated user.
     *
     * It always returns TRUE for administrative users.
     *
     * @param mixed $group Group to check against, this can be either a midcom_core_group object or a group string identifier.
     * @param midcom_core_user The user which should be checked, defaults to the current user.
     * @return boolean Indicating membership state.
     */
    function is_group_member($group, $user = null)
    {
        // Default parameter
        if (is_null($user))
        {
            if (is_null($this->user))
            {
                // not authenticated
                return false;
            }
            $user =& $this->user;
        }

        if ($this->admin)
        {
            // Administrators always have access.
            return true;
        }

        return $user->is_in_group($group);
    }

    /**
     * Returns true if there is an authenticated user, false otherwise.
     *
     * @return boolean True if there is a user logged in.
     */
    function is_valid_user()
    {
        return (! is_null($this->user));
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
    function require_do($privilege, &$content_object, $message = null)
    {
        if (! $this->can_do($privilege, $content_object))
        {
            if (is_null($message))
            {
                $string = $_MIDCOM->i18n->get_string('access denied: privilege %s not granted', 'midcom');
                $message = sprintf($string, $privilege);
            }
            $this->access_denied($message);
            // This will exit.
        }
    }

    /**
     * Validates, whether the given user have the privilege assigned to him in general.
     * Be aware, that this does not take any permissions overridden by content objects
     * into account. Whenever possible, you should user the can_do() variant of this
     * call therefore. can_user_do is only of interest in cases where you do not have
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
    function require_user_do($privilege, $message = null, $class = null)
    {
        if (! $this->can_user_do($privilege, null, $class))
        {
            if (is_null($message))
            {
                $string = $_MIDCOM->i18n->get_string('access denied: privilege %s not granted', 'midcom');
                $message = sprintf($string, $privilege);
            }
            $this->access_denied($message);
            // This will exit.
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
        if (! $this->is_group_member($group))
        {
            if (is_null($message))
            {
                $string = $_MIDCOM->i18n->get_string('access denied: user is not member of the group %s', 'midcom');
                if (is_object($group))
                {
                    $message = sprintf($string, $group->name);
                }
                else
                {
                    $message = sprintf($string, $group);
                }
            }

            $this->access_denied($message);
            // This will exit.
        }
    }

    /**
     * Validates that we currently have admin level privileges, which can either
     * come from the current user, or from the sudo service.
     *
     * If the check is successful, the function returns silently.
     * @param string $message The message to show if the admin level privileges are missing..
     */
    function require_admin_user($message = null)
    {
        if ($message === null)
        {
            $message = $_MIDCOM->i18n->get_string('access denied: admin level privileges required', 'midcom');
        }
        if (   ! $this->admin
            && ! $this->_component_sudo)
        {
            $this->access_denied($message);
            // This will exit.
        }
    }

    /**
     * Validates that there is an authenticated user.
     *
     * If this is not the case, the regular login page is shown automatically, see
     * show_login_page() for details..
     *
     * If the check is successful, the function returns silently.
     *
     * @param string $method Preferred authentication method: form or basic
     */
    function require_valid_user($method = 'form')
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        debug_print_function_stack("require_valid_user called at this level");
        debug_pop();
        if (! $this->is_valid_user())
        {
            switch ($method)
            {
                case 'basic':
                    $this->_http_basic_auth();
                    break;

                case 'form':
                default:
                    $this->show_login_page();
                    // This will exit.
            }
        }
    }

    /**
     * Handles HTTP Basic authentication
     */
    function _http_basic_auth()
    {
        $sg_name = 'SG0';
        if ($_MIDGARD['sitegroup'])
        {
            $sitegroup = new midgard_sitegroup($_MIDGARD['sitegroup']);
            $sg_name = $sitegroup->name;
        }

        if (!isset($_SERVER['PHP_AUTH_USER']))
        {
            _midcom_header("WWW-Authenticate: Basic realm=\"{$sg_name}\"");
            _midcom_header('HTTP/1.0 401 Unauthorized');
            // TODO: more fancy 401 output ?
            echo "<h1>Authorization required</h1>\n";
            $_MIDCOM->finish();
            _midcom_stop_request();
        }
        else
        {
            if (!$this->sessionmgr->create_login_session($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']))
            {
                // Wrong password: Recurse until auth ok or user gives up
                unset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
                $this->_http_basic_auth();
            }
            // Figure out how to update midcom auth status
            $_MIDCOM->auth->_initialize_user_from_midgard();
        }
    }

    /**
     * Returns a listing of all known(!) virtual groups.
     *
     * @return array An associative vgroup_id (including the vgroup: prefix) => vgroup_name listing.
     */
    function get_all_vgroups()
    {
        debug_push_class(__CLASS__, __FUNCTION__);

        if (is_null($this->_vgroups))
        {
            $qb = new midgard_query_builder('midcom_core_group_virtual_db');
            $result = @$qb->execute();
            if (! $result)
            {
                $this->_vgroups = Array();
            }
            else
            {
                foreach ($result as $vgroup_entry)
                {
                    $id = "vgroup:{$vgroup_entry->component}-{$vgroup_entry->identifier}";
                    $this->_vgroups[$id] = $vgroup_entry->name;
                }
            }
        }

        debug_pop();
        return $this->_vgroups;
    }

    /**
     * Factory Method: Resolves any assignee identifier known by the system into an appropriate
     * user/group object.
     *
     * You must adhere the reference that is returned, otherwise the internal caching
     * and runtime state strategy will fail.
     *
     * @param string $id A valid user or group identifier useable as assignee (e.g. the $id member
     *     of any midcom_core_user or midcom_core_group object).
     * @return object A reference to the corresponding object or false on failure.
     */
    function get_assignee($id)
    {
        $result = null;

        $parts = explode(':', $id);

        switch ($parts[0])
        {
            case 'user':
                $result = $this->get_user($id);
                break;

            case 'group':
            case 'vgroup':
                $result = $this->get_group($id);
                break;

            default:
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("The identifier {$id} cannot be resolved into an assignee, it cannot be mapped to a type.", MIDCOM_LOG_WARN);
                debug_pop();
                break;
        }

        return $result;
    }

    /**
     * This is a wrapper for get_user, which allows user retrieval by its name.
     * If the username is unknown, false is returned.
     *
     * @param string $name The name of the user to look up.
     * @return midcom_core_user A reference to the user object matching the username,
     *     or false if the username is unknown.
     */
    function get_user_by_name($name)
    {
        $qb = new midgard_query_builder($GLOBALS['midcom_config']['person_class']);
        $qb->add_constraint('username', '=', $name);
        $result = @$qb->execute();
        if (   !$result
            || count($result) == 0)
        {
            return false;
        }
        return $this->get_user($result[0]);
    }

    /**
     * This is a wrapper for get_user, which allows user retrieval by its email address.
     * If the email is empty or unknown, false is returned.
     *
     * @param string $email The email of the user to look up.
     * @return array|midcom_core_user A reference to the user object matching the email, array if multiple matches
     *     or false if the email is unknown.
     */
    function get_user_by_email($email)
    {
        static $persons_by_email = array();

        if (empty($email))
        {
            return false;
        }

        if (array_key_exists($email, $persons_by_email))
        {
            return $persons_by_email[$email];
        }

        // Seek user based on the primary email field
        $qb = new midgard_query_builder($GLOBALS['midcom_config']['person_class']);
        $qb->add_constraint('email', '=', $email);

        // FIXME: Some sites like maemo.org instead of deleting users just remove their account and prefix firstname by "DELETE "
        $qb->add_constraint('firstname', 'NOT LIKE', 'DELETE %');

        $results = @$qb->execute();

        if (   !$results
            || count($results) == 0)
        {
            // Try finding user based on the other email fields
            $person_guids = array();
            $mc = new midgard_collector('midgard_parameter', 'value', $email);
            $mc->set_key_property('parentguid');
            $mc->add_constraint('domain', '=', 'org.imc.vcard:email');
            $mc->execute();
            $guids = $mc->list_keys();
            foreach ($guids as $guid => $array)
            {
                $person_guids[] = $guid;
            }

            if (empty($person_guids))
            {
                return false;
            }

            $qb = new midgard_query_builder($GLOBALS['midcom_config']['person_class']);
            $qb->add_constraint('guid', 'IN', $person_guids);

            // FIXME: Some sites like maemo.org instead of deleting users just remove their account and prefix firstname by "DELETE "
            $qb->add_constraint('firstname', 'NOT LIKE', 'DELETE %');

            $results = @$qb->execute();

            if (empty($results))
            {
                $persons_by_email[$email] = false;
                return false;
            }
        }

        if (count($results) > 1)
        {
            $persons_by_email[$email] = array();
            foreach ($results as $result)
            {
                $persons_by_email[$email][] = $this->get_user($result);
            }
            return $persons_by_email[$email];
        }

        $persons_by_email[$email] = $this->get_user($results[0]);
        return $persons_by_email[$email];
    }

    /**
     * This is a wrapper for get_group, which allows Midgard Group retrieval by its name.
     * If the group name is unknown, false is returned.
     *
     * In the case that more then one
     * group matches the given name, the first one is returned. Note, that this should not
     * happen as midgard group names should be unique according to the specs.
     *
     * @param string $name The name of the group to look up.
     * @return midcom_core_group A reference to the group object matching the group name,
     *     or false if the group name is unknown.
     */
    function & get_midgard_group_by_name($name,$sg_id=null)
    {
        //$sg_id = $sg_id == null || !is_integer($sg_id) ? $_MIDGARD['sitegroup'] : $sg_id;
        $qb = new midgard_query_builder('midgard_group');
        $qb->add_constraint('name', '=', $name);
        if (is_integer($sg_id))
        {
            $qb->add_constraint('sitegroup', '=', $sg_id);
        }
        $result = @$qb->execute();
        if (   ! $result
            || count($result) == 0)
        {
            $result = false;
            return $result;
        }
        $grp = $this->get_group($result[0]);
        return $grp;
    }

    /**
     * Factory Method: Loads a user from the database and returns an object instance.
     *
     * You must adhere the reference that is returned, otherwise the internal caching
     * and runtime state strategy will fail.
     *
     * @param mixed $id A valid identifier for a MidgardPerson: An existing midgard_person class
     *     or subclass thereof, a Person ID or GUID or a midcom_core_user identifier.
     * @return midcom_core_user A reference to the user object matching the identifier or false on failure.
     */
    function get_user($id)
    {
        $object = null;
        if (is_double($id))
        {
            // This is some crazy workaround for cases where the ID passed is a double
            // (coming from $_MIDGARD['user'] possibly) and is_object($id), again for
            // whatever reason, evaluates to true for that object...
            $id = (int) $id;
        }
        else if (is_object($id))
        {
            if (is_a($id, 'midcom_baseclasses_database_person'))
            {
                $id = $id->id;
                $object = null;
            }
            elseif (is_a($id, 'midgard_person'))
            {
                $object = $id;
                $id = $object->id;
            }
            else
            {
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_print_type('The passed argument was an object of an unsupported type:', $id, MIDCOM_LOG_WARN);
                debug_print_r('Complete object dump:', $id);
                debug_pop();

                return false;
            }
        }
        else if (   ! is_string($id)
                 && ! is_integer($id))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_print_type('The passed argument was an object of an unsupported type:', $id, MIDCOM_LOG_WARN);
            debug_print_r('Complete object dump:', $id);
            debug_pop();

            return false;
        }

        if (! array_key_exists($id, $this->_user_cache))
        {
            if (is_null($object))
            {
                $this->_user_cache[$id] = new midcom_core_user($id);
            }
            else
            {
                $this->_user_cache[$id] = new midcom_core_user($object);
            }
        }

        // Keep it silent while missing user object can mess here
        if (!@$this->_user_cache[$id]->guid)
        {
            $this->_user_cache[$id] = false;
        }

        return $this->_user_cache[$id];
    }

    /**
     * Returns a midcom_core_group instance. Valid arguments are either a valid group identifier
     * (group:... or vgroup:...), any valid identifier for the midcom_core_group
     * constructor or a valid object of that type.
     *
     * You must adhere the reference that is returned, otherwise the internal caching
     * and runtime state strategy will fail.
     *
     * @param mixed $id The identifier of the group as outlined above.
     * @return midcom_core_group A group object instance matching the identifier, or false on failure.
     */
    function get_group($id)
    {
        $group = false;
        if (   is_object($id)
            && (   is_a($id, 'midcom_baseclasses_database_group')
                || is_a($id, 'midgard_group')))
        {
            $object = $id;
            $id = "group:{$id->guid}";
            if (! array_key_exists($id, $this->_group_cache))
            {
                $this->_group_cache[$id] = new midcom_core_group_midgard($object->id);
            }
        }
        else if (is_string($id))
        {
            if (! array_key_exists($id, $this->_group_cache))
            {
                $id_parts = explode(':', $id);
                if (count($id_parts) == 2)
                {
                    // This is a (v)group:... identifier
                    switch ($id_parts[0])
                    {
                        case 'group':
                            $this->_group_cache[$id] = new midcom_core_group_midgard($id_parts[1]);
                            break;

                        case 'vgroup':
                            $this->_group_cache[$id] = new midcom_core_group_virtual($id_parts[1]);
                            break;

                        default:
                            $this->_group_cache[$id] = false;
                            debug_push_class(__CLASS__, __FUNCTION__);
                            debug_add("The group type identifier {$id_parts[0]} is unknown, no group was loaded.", MIDCOM_LOG_WARN);
                            debug_pop();
                            break;
                    }
                }
                else
                {
                    // This must be a group ID, lets hope that the group_midgard constructor
                    // can take it.
                    $tmp = new midcom_core_group_midgard($id);
                    if (! $tmp)
                    {
                        $this->_group_cache[$id] = false;
                        debug_push_class(__CLASS__, __FUNCTION__);
                        debug_add("The group type identifier {$id} is of an invalid type, no group was loaded.", MIDCOM_LOG_WARN);
                        debug_pop();
                    }
                    else
                    {
                        $id = $tmp->id;
                        $this->_group_cache[$id] = $tmp;
                    }
                }
            }
        }
        else if (is_int($id))
        {
            // Looks like an object ID, again we try the midgard group constructor.
            $tmp = new midcom_core_group_midgard($id);
            if (! $tmp)
            {
                $this->_group_cache[$id] = false;
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("The group type identifier {$id} is of an invalid type, no group was loaded.", MIDCOM_LOG_WARN);
                debug_pop();
            }
            else
            {
                $id = $tmp->id;
                $this->_group_cache[$id] = $tmp;
            }
        }
        else
        {
            $this->_group_cache[$id] = false;
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("The group type identifier {$id} is of an invalid type, no group was loaded.", MIDCOM_LOG_WARN);
            debug_pop();
        }

        return $this->_group_cache[$id];
    }

    /**
     * Delete a registered virtual group in the system. This requires the privilege
     * midcom:vgroup_delete assigned to the user (there is no content object checked).
     *
     * @param midcom_core_group_virtual $virtual_group The group to drop, loaded by get_group() previously.
     * @return boolean Indicating success.
     */
    function delete_virtual_group($virtual_group)
    {
        debug_push_class(__CLASS__, __FUNCTION__);

        $this->require_user_do('midcom:vgroup_delete', 'You need the privilege "midcom:vgroup_register" to register any virtual group.');

        if (   ! is_a($virtual_group, 'midcom_core_group_virtual')
            || is_null($virtual_group->_storage)
            || ! $virtual_group->_storage->id)
        {
            debug_add('The virtual group passed cannot be removed, the object is invalid. See the debug level log for more details.',
                MIDCOM_LOG_ERROR);
            debug_print_r('Passed object was:', $virtual_group);
            debug_pop();
            return false;
        }

        if (! $virtual_group->_storage->delete())
        {
            debug_add("The virtual group {$virtual_group->id} cannot be removed, failed to delete the record: " . midcom_application::get_error_string(),
                MIDCOM_LOG_ERROR);
            debug_print_r('Passed object was:', $virtual_group);
            debug_pop();
            return false;
        }
        if (method_exists($virtual_group->_storage, 'purge'))
        {
            $virtual_group->_storage->purge();
        }

        if (array_key_exists($virtual_group->id, $this->_group_cache))
        {
            unset ($this->_group_cache[$virtual_group->id]);
        }

        debug_pop();
        return true;
    }

    /**
     * Register a virtual group in the system. This requires the privilege
     * midcom:vgroup_register assigned to the user (there is no content object checked).
     *
     * The member listing is retrieved by the callback
     * midcom_baseclasses_components_interface::_on_retrieve_vgroup_members().
     *
     * @param string $component The name to register a virtual group for.
     * @param string $identifier The component-local identifier of the virtual group.
     * @param string $name The clear-text name of the virtual group.
     * @return boolean Indicating success.
     */
    function register_virtual_group($component, $identifier, $name)
    {
        debug_push_class(__CLASS__, __FUNCTION__);

        $this->require_user_do('midcom:vgroup_register', 'You need the privilege "midcom:vgroup_register" to register any virtual group.');

        // Check whether we have a valid component URL here
        // Note, just trigger loading it is not an option, as this function might be
        // called during component loading itself.
        if (! $_MIDCOM->componentloader->validate_url($component))
        {
            debug_add("Failed to register the vgroup {$component}-{$identifier} ({$name}), the component name is invalid.",
                MIDCOM_LOG_ERROR);
            debug_add('The identifier must match the regular expression ^[a-z0-9]+$.');
            debug_pop();
            return false;
        }

        if (! preg_match('/^[a-z0-9]+$/', $identifier))
        {
            debug_add("Failed to register the vgroup {$component}-{$identifier} ({$name}), the identifier is invalid.",
                MIDCOM_LOG_ERROR);
            debug_add('The identifier must match the regular expression ^[a-z0-9]+$.');
            debug_pop();
            return false;
        }

        // Check if the group does already exist
        $qb = new midgard_query_builder('midcom_core_group_virtual_db');
        $qb->add_constraint('component', '=', $component);
        $qb->add_constraint('identifier', '=', $identifier);
        $tmp = @$qb->execute();

        if (   is_array($tmp)
            && count($tmp) > 0)
        {
            debug_add("Failing silently to register the vgroup {$component}-{$identifier} ({$name}), the group does already exist.",
                MIDCOM_LOG_INFO);
            debug_print_r('Resultset was:', $tmp);
            debug_pop();
            return true;
        }

        $obj = new midcom_core_group_virtual_db();
        $obj->component = $component;
        $obj->identifier = $identifier;
        $obj->name = $name;

        debug_print_r('Trying to create this Virtual Group:', $obj);

        if (   ! $obj->create()
            || ! $obj->id)
        {
            debug_add("Failed to register the vgroup {$component}-{$identifier} ({$name}), could not create the database record: " . midcom_application::get_error_string(),
                MIDCOM_LOG_ERROR);
            debug_print_r('Tried to create this record:', $obj);
            debug_pop();
            return false;
        }

        debug_pop();
        return true;
    }

    /**
     * This call tells the backend to log in.
     */
    public function login($username, $password)
    {
        return $this->_auth_backend->create_login_session($username, $password);
    }

    public function trusted_login($username)
    {
        if ($GLOBALS['midcom_config']['auth_allow_trusted'] !== true)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Trusted logins are prohibited", MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }

        return $this->_auth_backend->create_trusted_login_session($username);
    }

    /**
     * This call tells the backend to clear any authentication state, then relocates the
     * user to the sites' root URL, so that a new, unauthenticated request is started there.
     * If there was no user authenticated, the relocate is done nevertheless.
     *
     * It is optionally possible to override the destination set.
     *
     * @param string $destination The destination to relocate to after logging out.
     */
    function logout($destination = '')
    {
        $this->drop_login_session();
        $_MIDCOM->relocate($destination);
    }

    /**
     * This is a limited version of logout: It will just drop the current login session, but keep
     * the request running. This means, that the current request will stay authenticated, but
     * any subsequent requests not.
     *
     * Note, that this call will also drop any information in the PHP Session (if exists). This will
     * leave the request in a clean state after calling this function.
     */
    function drop_login_session()
    {
        if (is_null($this->_auth_backend->user))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('The backend has no authenticated user set, so we should be fine, doing the relocate nevertheless though.');
            debug_pop();
        }
        else
        {
            $this->_auth_backend->logout();
        }

        // Kill the session forcibly:
        @session_start();
        $_SESSION = Array();
        session_destroy();
    }

    function _generate_http_response()
    {
        if (_midcom_headers_sent())
        {
            // We have sent output to browser already, skip setting headers
            return false;
        }

        switch ($GLOBALS['midcom_config']['auth_login_form_httpcode'])
        {
            case 200:
                _midcom_header('HTTP/1.0 200 OK');
                break;

            case 403:
            default:
                _midcom_header('HTTP/1.0 403 Forbidden');
                break;
        }
    }

    /**
     * This is called by $_MIDCOM->generate_error(MIDCOM_ERRFORBIDDEN, ...) if and only if
     * the headers have not yet been sent. It will display the error message and appends the
     * login form below it.
     *
     * The function will clear any existing output buffer, and the sent page will have the
     * 403 - Forbidden HTTP Status. The login will relocate to the same URL, so it should
     * be mostly transparent.
     *
     * The login message shown depends on the current state:
     * - If an authentication attempt was done but failed, an appropriated wrong user/password
     *   message is shown.
     * - If the user is authenticated, a note that he might have to switch to a user with more
     *   privileges is shown.
     * - Otherwise, no message is shown.
     *
     * This function will exit() unconditionally.
     *
     * If the style element <i>midcom_services_auth_access_denied</i> is defined, it will be shown
     * instead of the default error page. The following variables will be available in the local
     * scope:
     *
     * $title contains the localized title of the page, based on the 'access denied' string ID of
     * the main MidCOM L10n DB. $message will contain the notification what went wrong and
     * $login_warning will notify the user of a failed login. The latter will either be empty
     * or enclosed in a paragraph with the CSS ID 'login_warning'.
     *
     * @link http://www.midgard-project.org/midcom-permalink-c5e99db3cfbb779f1108eff19d262a7c further information about how to style these elements.
     * @param string $message The message to show to the user.
     */
    function access_denied($message)
    {
        debug_push(__CLASS__, __FUNCTION__);

        debug_print_function_stack("access_denied was called from here:");

        // Determine login message
        $login_warning = '';
        if (! is_null($this->user))
        {
            // The user has insufficient privileges
            $login_warning = $_MIDCOM->i18n->get_string('login message - insufficient privileges', 'midcom');
        }
        else if ($this->auth_credentials_found)
        {
            $login_warning = $_MIDCOM->i18n->get_string('login message - user or password wrong', 'midcom');
        }

        if (   isset($_MIDGARD['config']['ragnaland'])
            && $_MIDGARD['config']['ragnaland'])
        {
            // We're running under Ragnaland, delegate logins to Midgard MVC
            throw new midgardmvc_exception_unauthorized($login_warning);
        }

        $title = $_MIDCOM->i18n->get_string('access denied', 'midcom');

        // Emergency check, if headers have been sent, kill MidCOM instantly, we cannot output
        // an error page at this point (dynamic_load from site style? Code in Site Style, something
        // like that)
        if (_midcom_headers_sent())
        {
            debug_add('Cannot render an access denied page, page output has already started. Aborting directly.', MIDCOM_LOG_INFO);
            echo "<br />{$title}: {$login_warning}";
            $_MIDCOM->finish();
            debug_add("Emergency Error Message output finished, exiting now", MIDCOM_LOG_DEBUG);
            _midcom_stop_request();
        }

        // Drop any output buffer first, hack this into the content cache.
        while (@ob_end_clean())
            // Empty Loop
        ;
        $_MIDCOM->cache->content->_obrunning = false;

        $this->_generate_http_response();

        $_MIDCOM->cache->content->no_cache();


        if (   function_exists('mgd_is_element_loaded')
            && mgd_is_element_loaded('midcom_services_auth_access_denied'))
        {
            // Pass our local but very useful variables on to the style element
            $GLOBALS['midcom_services_auth_access_denied_message'] = $message;
            $GLOBALS['midcom_services_auth_access_denied_title'] = $title;
            $GLOBALS['midcom_services_auth_access_denied_login_warning'] = $login_warning;
            midcom_show_element('midcom_services_auth_access_denied');
        }
        else
        {
            $_MIDCOM->add_link_head
            (
                array
                (
                    'rel' => 'stylesheet',
                    'type' => 'text/css',
                    'href' => MIDCOM_STATIC_URL.'/midcom.services.auth/style.css',
                )
            );
            echo '<?'.'xml version="1.0" encoding="ISO-8859-1"?'.">\n";
            ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
    <head>
        <title><?php echo $title; ?></title>
        <?php echo $_MIDCOM->print_head_elements(); ?>
    </head>

    <body onload="self.focus();document.midcom_services_auth_frontend_form.username.focus();">
        <div id="container">
            <div id="branding">
                <div id="title"><h1>Midgard CMS</h1><h2><?php echo $title; ?></h2></div>
                <div id="grouplogo"><a href="http://www.midgard-project.org/"><img src="<?php echo MIDCOM_STATIC_URL; ?>/stock-icons/logos/midgard-bubble-104x104.gif" width="104" height="104" /></a></div>
            </div>
            <div class="clear"></div>
            <div id="content">
                <div id="login">
                    <?php
                    $_MIDCOM->auth->show_login_form();
                    ?>
                    <div class="clear"></div>
                </div>

                <div id="error"><?php echo "<div>{$login_warning}</div><div>{$message}</div>"; ?></div>
            </div>

            <div id="bottom">
                <div id="version">Midgard <?php echo substr(mgd_version(), 0, 4); ?></div>
            </div>

            <div id="footer">
                <div class="midgard">
                    Copyright &copy; 1998-2008 <a href="http://www.midgard-project.org/">The Midgard Project</a>. Midgard is <a href="http://en.wikipedia.org/wiki/Free_software">free software</a> available under <a href="http://www.gnu.org/licenses/lgpl.html">GNU Lesser General Public License</a>.
                </div>
            </div>
    </body>
</html>
            <?php
        }
        $_MIDCOM->finish();
        debug_add("Error Page output finished, exiting now", MIDCOM_LOG_DEBUG);
        _midcom_stop_request();
    }

    /**
     * This function should be used to render the main login form. This does only include the form,
     * no heading or whatsoever.
     *
     * It is recommended to call this function only as long as the headers are not yet sent (which
     * is usually given thanks to MidCOMs output buffering).
     *
     * What gets rendered depends on the authentication frontend, but will usually be some kind
     * of form. The output from the frontend is surrounded by a div tag whose CSS ID is set to
     * 'midcom_login_form'.
     *
     * @link http://www.midgard-project.org/midcom-permalink-c5e99db3cfbb779f1108eff19d262a7c further information about how to style these elements.
     */
    function show_login_form()
    {
        echo "<div id='midcom_login_form'>\n";
        $this->_auth_frontend->show_authentication_form();
        echo "</div>\n";
    }

    /**
     * This will show a complete login page unconditionally and exit afterwards.
     * If the current style has an element called <i>midcom_services_auth_login_page</i>
     * it will be shown instead. The local scope will contain the two variables
     * $title and $login_warning. $title is the localized string 'login' from the main
     * MidCOM L10n DB, login_warning is empty unless there was a failed authentication
     * attempt, in which case it will have a localized warning message enclosed in a
     * paragraph with the ID 'login_warning'.
     *
     * @link http://www.midgard-project.org/midcom-permalink-c5e99db3cfbb779f1108eff19d262a7c further information about how to style these elements.
     */
    function show_login_page()
    {
        debug_push(__CLASS__, __FUNCTION__);

        // Drop any output buffer first, hack this into the content cache.
        while (@ob_end_clean())
            // Empty Loop
        ;

        $this->_generate_http_response();

        $_MIDCOM->cache->content->_obrunning = false;
        $_MIDCOM->cache->content->no_cache();

        $title = $_MIDCOM->i18n->get_string('login', 'midcom');

        if (   isset($_MIDGARD['config']['ragnaland'])
            && $_MIDGARD['config']['ragnaland'])
        {
            // We're running under Ragnaland, delegate logins to Midgard MVC
            throw new midgardmvc_exception_unauthorized($title);
        }

        // Determine login warning so that wrong user/pass is shown.
        $login_warning = '';
        if (   $this->auth_credentials_found
            && is_null($this->user))
        {
            $login_warning = $_MIDCOM->i18n->get_string('login message - user or password wrong', 'midcom');
        }


        if (   function_exists('mgd_is_element_loaded')
            && mgd_is_element_loaded('midcom_services_auth_login_page'))
        {
            // Pass our local but very useful variables on to the style element
            $GLOBALS['midcom_services_auth_show_login_page_title'] = $title;
            $GLOBALS['midcom_services_auth_show_login_page_login_warning'] = $login_warning;
            midcom_show_element('midcom_services_auth_login_page');
        }
        else
        {
            $_MIDCOM->add_link_head
            (
                array
                (
                    'rel' => 'stylesheet',
                    'type' => 'text/css',
                    'href' => MIDCOM_STATIC_URL.'/midcom.services.auth/style.css',
                )
            );
            echo '<?'.'xml version="1.0" encoding="UTF-8"?'.">\n";
            ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
    <head>
        <title><?php echo $title; ?></title>
        <?php echo $_MIDCOM->print_head_elements(); ?>
    </head>

    <body onload="self.focus();document.midcom_services_auth_frontend_form.username.focus();">
        <div id="container">
            <div id="branding">
                <div id="title"><h1>Midgard CMS</h1><h2><?php echo $title; ?></h2></div>
                <div id="grouplogo"><a href="http://www.midgard-project.org/"><img src="<?php echo MIDCOM_STATIC_URL; ?>/stock-icons/logos/midgard-bubble-104x104.gif" width="104" height="104" /></a></div>
            </div>
            <div class="clear"></div>
            <div id="content">
                <div id="login">
                    <?php
                    $_MIDCOM->auth->show_login_form();
                    ?>
                    <div class="clear"></div>
                </div>
                <?php
                if ($login_warning == '')
                {
                    echo "<div id=\"ok\">" . $_MIDCOM->i18n->get_string('login message - please enter credentials', 'midcom') . "</div>\n";
                }
                else
                {
                    echo "<div id=\"error\">{$login_warning}</div>\n";
                }
                ?>
            </div>

            <div id="bottom">
                <div id="version">Midgard <?php echo substr(mgd_version(), 0, 4); ?></div>
            </div>

            <div id="footer">
                <div class="midgard">
                    Copyright &copy; 1998-2008 <a href="http://www.midgard-project.org/">The Midgard Project</a>. Midgard is <a href="http://en.wikipedia.org/wiki/Free_software">free software</a> available under <a href="http://www.gnu.org/licenses/lgpl.html">GNU Lesser General Public License</a>.
                </div>
            </div>
    </body>
    <?php
    $_MIDCOM->uimessages->show();
    ?>
</html>
            <?php
        }
        $_MIDCOM->finish();
        _midcom_stop_request();
    }

}
?>
