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
     * @var midcom_services_session
     */
    private $session;

    /**
     * Read the configuration
     */
    public function __construct($auth)
    {
        $this->_cookie_id .= midcom::get()->config->get('auth_backend_simple_cookie_id');
        parent::__construct($auth);
    }

    public function read_session(Request $request)
    {
        if (!$request->hasPreviousSession()) {
            return false;
        }

        $this->session = new midcom_services_session($this->_cookie_id);
        if (!$this->session->exists('userid')) {
            return false;
        }
        return [
            'userid' => $this->session->get('userid'),
            'clientip' => $this->session->get('clientip'),
            'timestamp' => $this->session->get('timestamp')
        ];
    }

    public function create_session($clientip, midcom_core_user $user)
    {
        if (empty($clientip)) {
            $clientip = $_SERVER['REMOTE_ADDR'];
        }

        $this->session = new midcom_services_session($this->_cookie_id);
        $this->session->set('userid', $user->id);
        $this->session->set('clientip', $clientip);
        $this->session->set('timestamp', time());
        midcom::get()->session->migrate();
        return true;
    }

    public function update_session()
    {
        $this->session->set('timestamp', time());
    }

    public function delete_session()
    {
        midcom::get()->session->remove($this->_cookie_id);
    }
}
