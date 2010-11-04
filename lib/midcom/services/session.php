<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org 
 * @version $Id: session.php 25319 2010-03-18 12:44:12Z indeyets $
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
 * $session = new midcom_service_session();
 * if ($session->exists("mykey")) {
 *     echo "found session value: " . $session->get ("mykey") . ", removing it.";
 *     $value = $session->remove("mykey");
 * } else {
 *     echo "setting session value...";
 *     $session->set("mykey", "hello world");
 * }
 * ?>
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
 * It has the same public member functions as midcom_service__sessioning, refer
 * to this class for a detailed documentation.
 * 
 * Basically this wrapper ensures the singleton pattern is maintained and provides
 * you with an easy way of lock the domain you're working in.
 * 
 * @package midcom.services
 * @see midcom_service__sessioning
 */
class midcom_service_session
{
    
    /**
     * Sessioning singleton.
     * 
     * @var midcom_service__sessioning
     * @access private
     */
    var $_sessioning;
    
    /** 
     * The domain we're working in.
     * 
     * @var string
     * @access private
     */
    var $_domain;
    
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
    function __construct($context = null)
    {
        if (is_null($context))
        {
            $this->_domain = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_COMPONENT);
        }
        else if (is_numeric($context)
            || is_int($context))
        {
            $this->_domain = $_MIDCOM->get_context_data($context, MIDCOM_CONTEXT_COMPONENT);
        }
        else
        {
            $this->_domain = $context;
        }
        
        $this->_sessioning = $_MIDCOM->get_service("session");
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
     * @return mixed        The session key's data value, or NULL on failure.
     * @see midcom_service_session::exists()
     */
    function get ($key)
    {
        return $this->_sessioning->get($this->_domain, $key);
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
    function set ($key, $value)
    {
        $this->_sessioning->set($this->_domain, $key, $value);
    }
    
    /**
     * Checks, if the specified key has been added to the session store.
     * 
     * This is often used in conjunction with get to verify a keys existence.
     * 
     * @param mixed $key    The key to query.
     * @return boolean            Indicating availability.
     */
    function exists ($key)
    {
        return $this->_sessioning->exists($this->_domain, $key);
    }
    
    /**
     * Removes the value associated with the specified key. Returns null if the key
     * is non-existent or the value of the key just removed otherwise. Note, that 
     * this is not necessarily a valid non-existence check, as the sessioning 
     * system does allow null values. Use the exists function if unsure.
     * 
     * @param mixed $key    The key to remove.
     * @return mixed        The session key's data value, or NULL on failure.
     * @see midcom_service_session::exists()
     */
    function remove ($key)
    {
        return $this->_sessioning->remove($this->_domain, $key);
    }
    
    /**
     * Compatibility wrapper with other PHP sessioning setups
     */
    function del($key)
    {
        return $this->remove($key);
    }
    
    /**
     * Get all the session data
     * 
     * @access public
     * @return Array containing session data
     */
    function get_session_data()
    {
        return $this->_sessioning->get_session_data($this->_domain);
    }
}
?>