<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Attribute\NamespacedAttributeBag;

/**
 * Base singleton class of the MidCOM sessioning service.
 *
 * This is a singleton class, that is accessible through the MidCOM Service
 * infrastructure. It manages session data of MidCOM driven applications.
 *
 * This sessioning interface will always work with copies, never with references
 * to work around a couple of bugs mentioned in the details below.
 *
 * This class provides a generic interface to store keyed session values in the
 * domain of the corresponding component.
 *
 * All requests involving this service will always be flagged as no_cache.
 *
 * If you store class instances within a session, which is perfectly safe in
 * general, there are known problems due to the fact, that a class declaration
 * has to be available before it can be deserialized. As PHP sessioning does this
 * deserialization automatically, this might fail with MidCOM, where the sequence
 * in which the code gets loaded and the sessioning gets started up is actually
 * undefined. To get around this problems, the sessioning system stores not the
 * actual data in the sessioning array, but a serialized string of the data, which
 * can always be deserialized on PHP sessioning startup (its a string after all).
 * This has an important implication though: The sessioning system always stores
 * copies of the data, not references. So if you put something in to the session
 * store and modify it afterwards, this change will not be reflected in the
 * sessioning store.
 *
 * <b>Important:</b>
 *
 * Do <b>never</b> create an instance of this class directly. This is handled
 * by the framework. Instead use midcom_service_session which ensures the
 * singleton pattern.
 *
 * Do <b>never</b> work directly with the $_SESSION["midcom_session_data"]
 * variable, this is a 100% must-not, as this will break functionality.
 *
 * @package midcom.services
 * @see midcom_services_session
 */
class midcom_services__sessioning
{
    /**
     *
     * @var Session
     */
    private $session;

    /**
     * The AttributeBag's namespace separator
     *
     * @var string
     */
    private $ns_separator = '/';

    private function _initialize($unconditional_start = false)
    {
        static $initialized = false;
        if ($initialized) {
            return true;
        }

        if (   !midcom::get()->config->get('sessioning_service_enable')
            && !(   midcom::get()->config->get('sessioning_service_always_enable_for_users')
                 && midcom_connection::get_user())) {
            return false;
        }

        $this->session = new Session(null, new NamespacedAttributeBag('midcom_session_data', $this->ns_separator));
        // Try to start session only if the client sends the id OR we need to set data
        if (   !isset($_REQUEST[$this->session->getName()])
            && !$unconditional_start) {
            return false;
        }

        try {
            $this->session->start();
        } catch (RuntimeException $e) {
            debug_add($e->getMessage(), MIDCOM_LOG_ERROR);
            return false;
        }

        $initialized = true;
        return true;
    }

    /**
     * Checks, if the specified key has been added to the session store.
     *
     * This is often used in conjunction with get to verify a keys existence.
     *
     * @param string $domain    The domain in which to search for the key.
     * @param mixed $key        The key to query.
     * @return boolean                Indicating availability.
     */
    public function exists($domain, $key)
    {
        if (!$this->_initialize(true)) {
            return false;
        }
        return $this->session->has($domain . $this->ns_separator . $key);
    }

    /**
     * Returns a value from the session.
     *
     * Returns null if the key
     * is non-existent. Note, that this is not necessarily a valid non-existence
     * check, as the sessioning system does allow null values. Use the exists function
     * if unsure.
     *
     * @param string $domain    The domain in which to search for the key.
     * @param mixed $key        The key to query.
     * @return mixed            The session key's data value, or null on failure.
     * @see midcom_services__sessioning::exists()
     */
    public function get($domain, $key)
    {
        static $no_cache = false;
        if (!$this->exists($domain, $key)) {
            debug_add("Request for the key '{$key}' in the domain '{$domain}' failed, because the key doesn't exist.");
            return null;
        }
        if (!$no_cache) {
            midcom::get()->cache->content->no_cache();
            $no_cache = true;
        }
        return $this->session->get($domain . $this->ns_separator . $key);
    }

    /**
     * Removes the value associated with the specified key. Returns null if the key
     * is non-existent or the value of the key just removed otherwise. Note, that
     * this is not necessarily a valid non-existence check, as the sessioning
     * system does allow null values. Use the exists function if unsure.
     *
     * @param string $domain    The domain in which to search for the key.
     * @param mixed $key        The key to remove.
     * @return mixed            The session key's data value, or null on failure.
     * @see midcom_services__sessioning::exists()
     */
    public function remove($domain, $key)
    {
        if (!$this->exists($domain, $key)) {
            return null;
        }
        $data = $this->session->get($domain . $this->ns_separator . $key);
        $this->session->remove($domain . $this->ns_separator . $key);
        return $data;
    }

    /**
     * This will store the value to the specified key.
     *
     * Note, that a _copy_ is stored,
     * the actual object is not referenced in the session data. You will have to update
     * it manually in case of changes.
     *
     * @param string $domain    The domain in which to search for the key.
     * @param mixed    $key        Session value identifier.
     * @param mixed    $value        Session value.
     */
    public function set($domain, $key, $value)
    {
        if (!$this->_initialize(true)) {
            return false;
        }
        static $no_cache = false;
        if (!$no_cache) {
            midcom::get()->cache->content->no_cache();
            $no_cache = true;
        }
        $this->session->set($domain . $this->ns_separator . $key, $value);
    }

    /**
     * Get the session data
     *
     * @param string $domain   Session domain
     * @return Array containing session values
     */
    public function get_session_data($domain)
    {
        if (!$this->session->exists($domain)) {
            return false;
        }

        return $this->session->get($domain);
    }

    /**
     * Get the session object
     *
     * @return Session
     */
    public function get_session()
    {
        $this->_initialize(true);
        return $this->session;
    }
}
