<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Provides sessioning for components.
 *
 * Use get, set and exists to access the sessioning data within your component.
 *
 * <b>Example:</b>
 *
 * <code>
 * <?php
 * $session = new midcom_services_session();
 * if ($session->exists("mykey")) {
 *     echo "found session value: " . $session->get ("mykey") . ", removing it.";
 *     $value = $session->remove("mykey");
 * } else {
 *     echo "setting session value...";
 *     $session->set("mykey", "hello world");
 * }
 * </code>
 *
 * You should keep in mind, that the isolation level by default is per-component,
 * not per-request. If you, for example, have the same component active twice
 * (through dynamic_load) you have to manually ensure, that each request is
 * treated separately. Unfortunately MidCOM cannot help you here.
 *
 * <b>Implementation Notes:</b>
 *
 * This is a simple wrapper that provides access to the sessioning singleton.
 * It has the same public member functions as midcom_services__sessioning, refer
 * to this class for a detailed documentation.
 *
 * Basically this wrapper ensures the singleton pattern is maintained and provides
 * you with an easy way of lock the domain you're working in.
 *
 * @package midcom.services
 * @see midcom_services__sessioning
 */
class midcom_services_session
{
    private midcom_services__sessioning $_sessioning;

    private ?string $_domain;

    /**
     * Constructs a session object.
     *
     * The default constructor will create a sessioning object within the domain
     * of the current context's component. This will be sufficient for almost all
     * actual uses of the sessions.
     *
     * If passed a string argument, this value is used as a domain. This
     * is useful for components that need sessioning while under dynamic_load
     * conditions or while used as a library.
     */
    public function __construct(string $domain = null)
    {
        $this->_domain = $domain ?? midcom_core_context::get()->get_key(MIDCOM_CONTEXT_COMPONENT);

        $this->_sessioning = midcom::get()->session;
    }

    /**
     * Returns a value from the session.
     *
     * Returns null if the key
     * is non-existent. Note, that this is not necessarily a valid non-existence
     * check, as the sessioning system does allow null values. Use the exists function
     * if unsure.
     */
    public function get(string $key)
    {
        $data = $this->_sessioning->get($this->_domain, []);
        return $data[$key] ?? null;
    }

    /**
     * This will store the value to the specified key.
     */
    public function set(string $key, $value)
    {
        $data = $this->_sessioning->get($this->_domain, []);
        $data[$key] = $value;
        $this->_sessioning->set($this->_domain, $data);
    }

    /**
     * Checks, if the specified key has been added to the session store.
     *
     * This is often used in conjunction with get to verify a keys existence.
     */
    public function exists(string $key) : bool
    {
        $data = $this->_sessioning->get($this->_domain, []);
        return array_key_exists($key, $data);
    }

    /**
     * Removes the specified key.
     */
    public function remove(string $key)
    {
        $data = $this->_sessioning->get($this->_domain, []);
        unset($data[$key]);
        $this->_sessioning->set($this->_domain, $data);
    }
}
