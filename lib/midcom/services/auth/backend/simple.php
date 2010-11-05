<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: simple.php 26511 2010-07-06 13:48:41Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/** Auth Backend base class */
require_once (MIDCOM_ROOT . '/midcom/services/auth/backend.php');

/**
 * The simple auth backend uses cookies to store a session identifier which
 * consists of the midgard person GUID.
 *
 * The validity of the cookie will be controlled by the configuration options
 * <i>auth_backend_simple_cookie_path</i> and <i>auth_backend_simple_cookie_domain</i>:
 *
 * The path defaults to $_MIDGARD['self']. If the domain is set to null (the default),
 * no domain is specified in the cookie, making it a traditional site-specific session
 * cookie. If it is set, the domain parameter of the cookie will be set accordingly.
 *
 * The basic cookie id (username prefix) is taken from the config option
 * <i>auth_backend_simple_cookie_id</i>, which defaults to the current host GUID.
 *
 * @package midcom.services
 */
class midcom_services_auth_backend_simple extends midcom_services_auth_backend
{
    /**
     * The auto-generated cookie ID for which this login session is valid. This consists
     * of a static string with the host GUID concatenated to it.
     */
    var $_cookie_id = 'midcom_services_auth_backend_simple-';

    /**
     * Read the configuration
     */
    function __construct($auth)
    {
        $this->_cookie_id .= $GLOBALS['midcom_config']['auth_backend_simple_cookie_id'];

        return parent::__construct($auth);
    }

    function read_login_session()
    {
        $reset_cookie = false;
        if (   array_key_exists($this->_cookie_id, $_GET)
            && !array_key_exists($this->_cookie_id, $_COOKIE))
        {
            /**
             * Loginbroker passed us the session data via GET (browsers can be very finicky about
             * cross-host cookies these days), make it available via $_COOKIE as well
             *
             * @todo checksumming ? (though hijacking this is only slightly simpler than hijacking cookies)
             */
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('Found cookie-id in _GET but not in _COOKIE, referencing', MIDCOM_LOG_INFO);
            $_COOKIE[$this->_cookie_id] =& $_GET[$this->_cookie_id];
            $reset_cookie = true;
            debug_pop();
        }

        if (! array_key_exists($this->_cookie_id, $_COOKIE))
        {
            //debug_push_class(__CLASS__, __FUNCTION__);
            //debug_add('There is no cookie, we cannot read the login session.');
            //debug_pop();
            return false;
        }

        $data = explode('-', $_COOKIE[$this->_cookie_id]);
        if (count($data) != 2)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("The cookie data could not be parsed, assuming tampered session.",
                MIDCOM_LOG_ERROR);
            debug_add('Killing the cookie...', MIDCOM_LOG_INFO);
            $this->_delete_cookie();
            debug_pop();
            return false;
        }

        $session_id = $data[0];
        $user_id = $data[1];
        $this->user = $this->auth->get_user($user_id);
        if (! $this->user)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("The user ID {$user_id} is invalid, could not load the user from the database, assuming tampered session.",
                MIDCOM_LOG_ERROR);
            debug_add('Killing the cookie...');
            $this->_delete_cookie();
            debug_pop();
            return false;
        }

        $this->session_id = $this->auth->sessionmgr->load_login_session($session_id, $this->user);

        if (! $this->session_id)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("The session {$this->session_id} is invalid (usually this means an expired session).",
                MIDCOM_LOG_ERROR);
            debug_add('Killing the cookie...');
            $this->_delete_cookie();
            debug_pop();
            return false;
        }

        if ($reset_cookie)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('Re-Setting of session cookie requested, doing it', MIDCOM_LOG_INFO);
            debug_pop();
            $this->_set_cookie();
        }

        return true;
    }

    /**
     * Sets the cookie according to the session configuration as outlined in the
     * class introduction.
     */
    function _set_cookie()
    {
        $secure_cookie = false;
        if (   isset($_SERVER['HTTPS'])
            && !empty($_SERVER['HTTPS'])
            && $GLOBALS['midcom_config']['auth_backend_simple_cookie_secure'])
        {
            $secure_cookie = true;
        }
        _midcom_setcookie
        (
            $this->_cookie_id,
            "{$this->session_id}-{$this->user->id}",
            0,
            $GLOBALS['midcom_config']['auth_backend_simple_cookie_path'],
            $GLOBALS['midcom_config']['auth_backend_simple_cookie_domain'],
            $secure_cookie
        );
    }

    /**
     * Deletes the cookie according to the session configuration as outlined in the
     * class introduction.
     */
    function _delete_cookie()
    {
        _midcom_setcookie
        (
            $this->_cookie_id,
            false,
            0,
            $GLOBALS['midcom_config']['auth_backend_simple_cookie_path'],
            $GLOBALS['midcom_config']['auth_backend_simple_cookie_domain']
        );
    }

    function _on_login_session_created()
    {
        $this->_set_cookie();
    }

    function _on_login_session_deleted()
    {
        $this->_delete_cookie();
    }
}

?>
