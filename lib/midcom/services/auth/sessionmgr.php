<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This class is responsible for managing login session, mainly concentrating on
 * DB I/O.
 *
 * Normally, you should not have to work with this class unless you are either
 * writing an authentication front or back end, or a component which includes online
 * status notifications and the like.
 *
 * The single instance of this class can be accessed as
 * midcom::get()->auth->sessionmgr.
 *
 * <b>Checking whether a user is online</b>
 *
 * The is-user-online check requires the user to have the privilege <i>midcom:isonline</i>
 * set on the user which he is trying to check.
 *
 * @package midcom.services
 */
class midcom_services_auth_sessionmgr
{
    /**
     * @var midcom_services_auth
     */
    private $auth;

    /**
     * Simple, currently empty default constructor.
     *
     * @param midcom_services_auth $auth Main authentication instance
     */
    public function __construct(midcom_services_auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Creates the session object
     *
     * @param string $clientip
     * @param midgard_user $mgd_user
     * @return midcom_core_login_session_db|false
     */
    public function create_session($clientip, midgard_user $mgd_user)
    {
        if (!$user = $this->auth->get_user($mgd_user->person)) {
            debug_add("Failed to create a new login session: No user found for person {$mgd_user->person}.", MIDCOM_LOG_ERROR);
            return false;
        }

        if (empty($clientip)) {
            $clientip = $_SERVER['REMOTE_ADDR'];
        }

        $session = new midcom_core_login_session_db;
        $session->userid = $user->id;
        $session->clientip = $clientip;
        $session->timestamp = time();
        if (!$session->create()) {
            debug_add('Failed to create a new login session: ' . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            return false;
        }
        return $session;
    }

    /**
     * Checks the system for a valid login session matching the passed arguments.
     * It validates the clients' IP, the user ID and the sesion timeout. If a valid
     * session is found, its ID is returned again, you can from now on use this as
     * a token for authentication.
     *
     * This code will implicitly clean up stale sessions for the current
     * user.
     *
     * @param string $sessionid The Session ID to check for.
     * @param string $clientip The client IP to check against
     * @return midcom_core_login_session_db The loaded session or false, in case there
     *     is no valid session.
     */
    public function load_login_session($sessionid, $clientip)
    {
        try {
            $session = new midcom_core_login_session_db($sessionid);
        } catch (Exception $e) {
            debug_add('Login session ' . $sessionid . ' failed to load: ' . $e->getMessage(), MIDCOM_LOG_INFO);
            return false;
        }

        if ($session->timestamp < $this->get_timeout()) {
            $session->purge();
            debug_add("The session {$session->guid} (#{$session->id}) has timed out.", MIDCOM_LOG_INFO);
            return false;
        }

        if (   midcom::get()->config->get('auth_check_client_ip')
            && $session->clientip != $clientip) {
            debug_add("The session {$session->guid} (#{$session->id}) had mismatching client IP.", MIDCOM_LOG_INFO);
            debug_add("Expected {$session->clientip}, got {$clientip}.");
            return false;
        }

        if ($session->timestamp < time() - midcom::get()->config->get('auth_login_session_update_interval')) {
            // Update the timestamp if previous timestamp is older than specified interval
            $session->timestamp = time();
            if (!$session->update()) {
                debug_add("Failed to update the session {$session->guid} (#{$session->id}) to the current timestamp: " . midcom_connection::get_error_string(), MIDCOM_LOG_INFO);
            }
        }

        return $session;
    }

    private function get_timeout()
    {
        if (!midcom::get()->config->get('auth_login_session_timeout')) {
            return 0;
        }
        return time() - midcom::get()->config->get('auth_login_session_timeout');
    }

    /**
     * Drop a session which has been previously loaded successfully.
     * Usually, you will use this during logouts.
     *
     * @param midcom_core_login_session_db $session The session to invalidate.
     * @return boolean Indicating success.
     */
    public function delete_session(midcom_core_login_session_db $session)
    {
        if (!$session->purge()) {
            debug_add("Failed to delete the delete session {$session->guid} (#{$session->id}): " . midcom_connection::get_error_string(), MIDCOM_LOG_INFO);
            return false;
        }
        return true;
    }

    public function _delete_user_sessions(midcom_core_user $user)
    {
        // Delete login sessions
        $qb = new midgard_query_builder('midcom_core_login_session_db');
        $qb->add_constraint('userid', '=', $user->id);
        foreach ($qb->execute() as $entry) {
            debug_add("Deleting login session ID {$entry->id} for user {$entry->userid} with timestamp {$entry->timestamp}");
            $entry->purge();
        }
    }

    /**
     * Checks the online state of a given user. You require the privilege midcom:isonline
     * for the user you are going to check. The privilege is not granted by default,
     * to allow users full control over their privacy.
     *
     * 'unknown' is returned in cases where you have insufficient permissions.
     *
     * @param midcom_core_user $user The user object which has been updated.
     * @return string One of 'online', 'offline' or 'unknown', indicating the current online
     *     state.
     */
    public function is_user_online($user)
    {
        if (!$user->get_storage()->can_do('midcom:isonline')) {
            return 'unknown';
        }

        $qb = new midgard_query_builder('midcom_core_login_session_db');
        $qb->add_constraint('userid', '=', $user->id);
        $qb->add_constraint('timestamp', '>=', $this->get_timeout());

        return $qb->count() > 0 ? 'online' : 'offline';
    }

    /**
     * Returns the total number of users online. This does not adhere the isonline check,
     * as there is no information about which users are online.
     *
     * The test is, as usual, heuristic, as it will count users which forgot to log off
     * as long as their session did not expire.
     *
     * @return int The count of users online
     */
    function get_online_users_count()
    {
        $mc = new midgard_collector('midcom_core_login_session_db');
        $mc->set_key_property('userid');
        $mc->add_constraint('timestamp', '>=', $this->get_timeout());
        $mc->execute();

        return count($mc->list_keys());
    }

    /**
     * Extended check for online users. Returns an array of guid=>midcom_core_user pairs of the users
     * which are currently online. This takes privileges into account and will thus only list
     * users which the current user has the privililege to observe.
     *
     * So the difference between get_online_users_count and the size of this result set is the number
     * of invisible users.
     *
     * @return Array List of visible users that are online.
     */
    function get_online_users()
    {
        $mc = new midgard_collector('midcom_core_login_session_db');
        $mc->set_key_property('userid');
        $mc->add_constraint('timestamp', '>=', $this->get_timeout());
        $mc->execute();

        $result = [];
        foreach (array_keys($mc->list_keys()) as $userid) {
            if (   ($user = $this->auth->get_user($userid))
                && $user->is_online()) {
                $result[$user->guid] = $user;
            }
        }

        return $result;
    }
}
