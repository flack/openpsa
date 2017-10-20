<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Request;

/**
 * The simple auth backend uses cookies to store a session identifier which
 * consists of the midgard person GUID.
 *
 * The validity of the cookie will be controlled by the configuration options
 * <i>auth_backend_simple_cookie_path</i> and <i>auth_backend_simple_cookie_domain</i>:
 *
 * The path defaults to midcom_connection::get_url('self'). If the domain is set to null (the default),
 * no domain is specified in the cookie, making it a traditional site-specific session
 * cookie. If it is set, the domain parameter of the cookie will be set accordingly.
 *
 * The basic cookie id (username prefix) is taken from the config option
 * <i>auth_backend_simple_cookie_id</i>, which defaults to 1
 *
 * @package midcom.services
 */
class midcom_services_auth_backend_simple extends midcom_services_auth_backend
{
    /**
     * The auto-generated cookie ID for which this login session is valid. This consists
     * of a static string with the host GUID concatenated to it.
     */
    private $_cookie_id = 'midcom_services_auth_backend_simple-';

    /**
     * Read the configuration
     */
    public function __construct($auth)
    {
        $this->_cookie_id .= midcom::get()->config->get('auth_backend_simple_cookie_id');
        parent::__construct($auth);
    }

    public function read_login_session(Request $request)
    {
        if (!$request->hasPreviousSession()) {
            return false;
        }
        $session = new midcom_services_session($this->_cookie_id);
        $session_id = $session->get('session_id');
        if (empty($session_id)) {
            return false;
        }

        $this->session = $this->auth->sessionmgr->load_login_session($session_id, $request->getClientIp());

        if (!$this->session) {
            debug_add("The session {$session_id} is invalid (usually this means an expired session).", MIDCOM_LOG_ERROR);
            $this->_on_login_session_deleted();
            return false;
        }

        $this->user = $this->auth->get_user($this->session->userid);
        if (!$this->user) {
            debug_add("The user ID {$this->session->userid} is invalid, could not load the user from the database, assuming tampered session.",
                MIDCOM_LOG_ERROR);
            $this->_on_login_session_deleted();
            return false;
        }

        return true;
    }

    public function _on_login_session_created()
    {
        $session = new midcom_services_session($this->_cookie_id);
        $session->set('session_id', $this->session->guid);
    }

    public function _on_login_session_deleted()
    {
        midcom::get()->session->clear();
    }
}
