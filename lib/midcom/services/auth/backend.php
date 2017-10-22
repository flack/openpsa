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
     * This variable holds the user that has been successfully authenticated by the class,
     * it is considered to be read-only.
     *
     * @var midcom_core_user
     */
    var $user;

    /**
     * @var midcom_services_auth
     */
    protected $auth = null;

    /**
     * The constructor should do only basic initialization.
     *
     * @param midcom_services_auth $auth Main authentication instance
     */
    public function __construct(midcom_services_auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * This function, always called first in the order of execution, should check
     * whether we have a usable login session. It has to use the login session management
     * system to load a login session. At the end of the successful execution of this
     * function, you have to populate the $session and $user members accordingly.
     *
     * @return boolean|array Return clientip, userid and timeout if the login session was successfully loaded, false
     *     otherwise.
     */
    abstract public function read_session(Request $request);

    /**
     * This is called immediately after a new login
     * The authentication driver has to ensure that the login identifier stays
     * available during subsequent requests.
     *
     * @param string $clientip
     * @param midcom_core_user $user
     * @return boolean Indicating success
     */
    abstract public function create_session($clientip, midcom_core_user $user);

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
     * Checks for a running login session.
     *
     * @param Request $request
     * @return boolean
     */
    public function check_for_active_login_session(Request $request)
    {
        $data = $this->read_session($request);
        if (!$data) {
            return false;
        }

        if (   midcom::get()->config->get('auth_check_client_ip')
            && $data['clientip'] != $request->getClientIp()) {
            debug_add("The session had mismatching client IP.", MIDCOM_LOG_INFO);
            debug_add("Expected {$data['clientip']}, got {$request->getClientIp()}.");
            return false;
        }

        $this->user = $this->auth->get_user($data['userid']);
        if (!$this->user) {
            debug_add("The user ID {$data['userid']} is invalid, could not load the user from the database, assuming tampered session.",
            MIDCOM_LOG_ERROR);
            $this->delete_session();
            return false;
        }

        if (   !$this->check_timestamp($data['timestamp'], $this->user)
            || !$this->authenticate($this->user->username, '', true)) {
            $this->logout();
            return false;
        }
        return true;
    }

    private function check_timestamp($timestamp, midcom_core_user $user)
    {
        $timeout = midcom::get()->config->get('auth_login_session_timeout', 0);
        if ($timeout > 0 && time() - $timeout > $timestamp) {
            debug_add("The session has timed out.", MIDCOM_LOG_INFO);
            return false;
        }

        if ($timestamp < time() - midcom::get()->config->get('auth_login_session_update_interval')) {
            // Update the timestamp if previous timestamp is older than specified interval
            $this->update_session();
            $person = $user->get_storage();
            $person->set_parameter('midcom', 'online', time());
        }
        return true;
    }

    /**
     * Does the actual Midgard authentication.
     *
     * @param string $username The name of the user to authenticate.
     * @param string $password The password of the user to authenticate.
     * @param boolean $trusted
     * @return boolean|midcom_core_user
     */
    public function authenticate($username, $password, $trusted = false)
    {
        if (empty($username)) {
            debug_add("Failed to authenticate: Username is empty.", MIDCOM_LOG_ERROR);
            return false;
        }
        if (!$trusted && empty($password)) {
            debug_add("Failed to authenticate: Password is empty.", MIDCOM_LOG_ERROR);
            return false;
        }

        $user = midcom_connection::login($username, $password, $trusted);

        if (!$user) {
            debug_add("Failed to authenticate the given user: ". midcom_connection::get_error_string(),
                    MIDCOM_LOG_INFO);
            return false;
        }

        return $this->auth->get_user($user->person);
    }

    /**
     * Creates a login session using the given credentials. It assumes that
     * no login has concluded earlier
     *
     * @param string $username The name of the user to authenticate.
     * @param string $password The password of the user to authenticate.
     * @param string $clientip The client IP to which this session is assigned to. This
     *     defaults to the client IP reported by the web server
     * @param boolean $trusted Do a trusted login
     * @return boolean Indicating success.
     */
    public function login($username, $password, $clientip = null, $trusted = false)
    {
        $this->user = $this->authenticate($username, $password, $trusted);
        if (!$this->user) {
            return false;
        }

        if ($this->create_session($clientip, $this->user)) {
            $person = $this->user->get_storage();
            $person->set_parameter('midcom', 'online', time());
            return true;
        }
        return false;
    }

    /**
     * Deletes login information and session
     */
    public function logout()
    {
        if ($person = $this->user->get_storage()) {
            $person->delete_parameter('midcom', 'online');
        }
        $this->delete_session();
    }
}
