<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Request;

/**
 * Authentication backend, responsible for validating user/password pairs and
 * mapping them to a given user as well as the "sessioning" part, e.g. the transition
 * of the authentication credentials over several requests.
 *
 * Configuration, if necessary, should be done using the MidCOM configuration
 * system, prefixing all values with 'auth_backend_$name_', e.g.
 * 'auth_backend_cookie_timeout'.
 *
 * @package midcom.services
 */
abstract class midcom_services_auth_backend
{
    /**
     * Internal cache of all loaded users, indexed by their identifiers.
     */
    private array $_user_cache = [];

    /**
     * This function, always called first in the order of execution, should check
     * whether we have a usable login session. It has to use the login session management
     * system to load a login session. At the end of the successful execution of this
     * function, you have to populate the $session and $user members accordingly.
     *
     * @return ?array clientip, userid and timeout if the login session was successfully loaded
     */
    abstract public function read_session(Request $request) : ?array;

    /**
     * This is called immediately after a new login
     * The authentication driver has to ensure that the login identifier stays
     * available during subsequent requests.
     */
    abstract public function create_session(?string $clientip, midcom_core_user $user) : bool;

    /**
     * This should delete the currently active login session,
     * which has been loaded by a previous call to read_session or created during
     * create_session.
     *
     * You should throw midcom_error if anything goes wrong here.
     */
    abstract public function delete_session();

    /**
     * Refresh the session's timestamp here
     */
    abstract public function update_session();

    /**
     * Load a user from the database and returns an object instance.
     *
     * @param mixed $id A valid identifier for a MidgardPerson: An existing midgard_person class
     *     or subclass thereof, a Person ID or GUID or a midcom_core_user identifier.
     */
    public function get_user($id) : ?midcom_core_user
    {
        $param = $id;

        if (isset($param->id)) {
            $id = $param->id;
        } elseif (!is_string($id) && !is_int($id)) {
            debug_add('The passed argument was an object of an unsupported type: ' . gettype($param), MIDCOM_LOG_WARN);
            debug_print_r('Complete object dump:', $param);
            return null;
        }
        if (!array_key_exists($id, $this->_user_cache)) {
            try {
                $this->_user_cache[$id] = new midcom_core_user($param);
            } catch (midcom_error $e) {
                // Keep it silent while missing user object can mess here
                $this->_user_cache[$id] = null;
            }
        }

        return $this->_user_cache[$id];
    }

    /**
     * Checks for a running login session.
     */
    public function check_for_active_login_session(Request $request) : ?midcom_core_user
    {
        if (!$data = $this->read_session($request)) {
            return null;
        }

        if (   midcom::get()->config->get('auth_check_client_ip')
            && $data['clientip'] != $request->getClientIp()) {
            debug_add("The session had mismatching client IP.", MIDCOM_LOG_INFO);
            debug_add("Expected {$data['clientip']}, got {$request->getClientIp()}.");
            return null;
        }

        if (!$user = $this->get_user($data['userid'])) {
            debug_add("The user ID {$data['userid']} is invalid, could not load the user from the database, assuming tampered session.",
            MIDCOM_LOG_ERROR);
            $this->delete_session();
            return null;
        }

        if (   !$this->check_timestamp($data['timestamp'], $user)
            || !$this->authenticate($user->username, '', true)) {
            $this->logout($user);
            return null;
        }
        return $user;
    }

    private function check_timestamp($timestamp, midcom_core_user $user) : bool
    {
        $timeout = midcom::get()->config->get('auth_login_session_timeout', 0);
        if ($timeout > 0 && time() - $timeout > $timestamp) {
            debug_add("The session has timed out.", MIDCOM_LOG_INFO);
            return false;
        }

        if ($timestamp < time() - midcom::get()->config->get('auth_login_session_update_interval')) {
            // Update the timestamp if previous timestamp is older than specified interval
            $this->update_session();
            $person = $user->get_storage()->__object;
            $person->set_parameter('midcom', 'online', time());
        }
        return true;
    }

    /**
     * Does the actual Midgard authentication.
     */
    public function authenticate(string $username, string $password, bool $trusted = false) : ?midcom_core_user
    {
        if (empty($username)) {
            debug_add("Failed to authenticate: Username is empty.", MIDCOM_LOG_ERROR);
            return null;
        }
        if (!$trusted && empty($password)) {
            debug_add("Failed to authenticate: Password is empty.", MIDCOM_LOG_ERROR);
            return null;
        }

        $user = midcom_connection::login($username, $password, $trusted);

        if (!$user) {
            debug_add("Failed to authenticate the given user: " . midcom_connection::get_error_string(), MIDCOM_LOG_INFO);
            return null;
        }

        return $this->get_user($user->person);
    }

    /**
     * Creates a login session using the given credentials. It assumes that
     * no login has concluded earlier
     */
    public function login(string $username, string $password, string $clientip = null, bool $trusted = false) : ?midcom_core_user
    {
        $user = $this->authenticate($username, $password, $trusted);

        if ($user && $this->create_session($clientip, $user)) {
            $person = $user->get_storage()->__object;
            $person->set_parameter('midcom', 'online', time());
            return $user;
        }
        return null;
    }

    /**
     * Deletes login information and session
     */
    public function logout(midcom_core_user $user)
    {
        if ($person = $user->get_storage()) {
            $person->__object->delete_parameters(['domain' => 'midcom', 'name' => 'online']);
        }
        $this->delete_session();
        midcom_connection::logout();
    }
}
