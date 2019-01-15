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
    /**
     * Sessioning singleton.
     *
     * @var midcom_services__sessioning
     */
    private $_sessioning;

    /**
     * The domain we're working in.
     *
     * @var string
     */
    private $_domain;

    /**
     * Constructs a session object.
     *
     * The constructor has three semantics:
     *
     * The default constructor will create a sessioning object within the domain
     * of the current context's component. This will be sufficient for almost all
     * actual uses of the sessions.
     *
     * If passed an integer argument, it will use the context indicated by this
     * parameter as a default domain.
     *
     * Finally, if passed a string argument, this value is used as a domain. This
     * is useful for components that need sessioning while under <i>dynamic_load</i>
     * conditions or while used as a <i>library</i>.
     *
     * @param mixed $context    Either null or a context ID (uses the context's component) or an explicit domain.
     */
    public function __construct($context = null)
    {
        if (   $context === null
            || is_numeric($context)) {
            $this->_domain = midcom_core_context::get($context)->get_key(MIDCOM_CONTEXT_COMPONENT);
        } else {
            $this->_domain = $context;
        }

        $this->_sessioning = midcom::get()->session;
    }

    /**
     * Returns a value from the session.
     *
     * Returns null if the key
     * is non-existent. Note, that this is not necessarily a valid non-existence
     * check, as the sessioning system does allow null values. Use the exists function
     * if unsure.
     *
     * @param mixed $key    The key to query.
     * @return mixed        The session key's data value, or null on failure.
     * @see midcom_services_session::exists()
     */
    public function get($key)
    {
        return $this->_sessioning->get($this->_domain . '/' . $key);
    }

    /**
     * This will store the value to the specified key.
     *
     * Note, that a _copy_ is stored,
     * the actual object is not referenced in the session data. You will have to update
     * it manually in case of changes.
     *
     * @param    mixed    $key    Session value identifier.
     * @param    mixed    $value    Session value.
     */
    public function set($key, $value)
    {
        $this->_sessioning->set($this->_domain . '/' . $key, $value);
    }

    /**
     * Checks, if the specified key has been added to the session store.
     *
     * This is often used in conjunction with get to verify a keys existence.
     *
     * @param mixed $key    The key to query.
     * @return boolean            Indicating availability.
     */
    public function exists($key)
    {
        return $this->_sessioning->has($this->_domain . '/' . $key);
    }

    /**
     * Removes the value associated with the specified key. Returns null if the key
     * is non-existent or the value of the key just removed otherwise. Note, that
     * this is not necessarily a valid non-existence check, as the sessioning
     * system does allow null values. Use the exists function if unsure.
     *
     * @param mixed $key    The key to remove.
     * @return mixed        The session key's data value, or null on failure.
     * @see midcom_services_session::exists()
     */
    public function remove($key)
    {
        return $this->_sessioning->remove($this->_domain . '/' . $key);
    }

    /**
     * Get all the session data
     *
     * @return Array containing session data
     */
    public function get_session_data()
    {
        return $this->_sessioning->get($this->_domain, false);
    }
}
