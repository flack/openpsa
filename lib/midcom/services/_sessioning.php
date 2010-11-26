<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: _sessioning.php 26510 2010-07-06 13:42:58Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

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
 * It will try to be as graceful as possible when starting up the sessioning. Note,
 * that side-effects that might occur together with NemeinAuth are not fully
 * investigated yet.
 *
 * <b>Important:</b>
 *
 * Do <b>never</b> create an instance of this class directly. This is handled
 * by the framework. Instead use midcocm_service_session which ensures the
 * singleton pattern.
 *
 * Do <b>never</b> work directly with the $_SESSION["midcom_session_data"]
 * variable, this is a 100% must-not, as this will break functionality.
 *
 * @package midcom.services
 * @access private
 * @see midcom_services_session
 */
class midcom_services__sessioning
{
    /**
     * The constructor will initialize the sessioning, set the output nocacheable
     * and initialize the session data. This might involve creating an empty
     * session array.
     */
    function __construct()
    {
        static $started = false;

        if ($started)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "MidCOM Sessioning has already been started, it must not be started twice. Aborting");
        }

        $started = true;
    }

    function _initialize($unconditional_start = false)
    {
        static $initialized = false;
        if ($initialized)
        {
            return true;
        }


        if (   !$GLOBALS['midcom_config']['sessioning_service_enable']
            && !(   $GLOBALS['midcom_config']['sessioning_service_always_enable_for_users']
                 && midcom_connection::get_user()
                 )
            )
        {
            return false;
        }

        // Try to start session only if the client sends the id OR we need to set data
        if (   !isset($_REQUEST[session_name()])
            && !$unconditional_start)
        {
            return false;
        }
        
        if (_midcom_headers_sent())
        {
            // Don't try starting a session if we're past the headers phase
            debug_add("Aborting session start, headers have already been sent", MIDCOM_LOG_WARN);
            return;
        }

        $track_state = ini_get('track_errors');
        ini_set('track_errors', true);
        @session_start();
        $session_err = null;
        if (isset($php_errormsg))
        {
            $session_err = (string)$php_errormsg;
        }
        ini_set('track_errors', $track_state);
        unset($track_state);
        if (!isset($_SESSION))
        {
            debug_add("\$_SESSION is not set, error message was: {$session_err}", MIDCOM_LOG_ERROR);
            unset($session_err, $php_errormsg);
            return false;
        }
        unset($session_err);

        /* Cache disabling made conditional based on domain/key existence */

        // Check for session data and load or initialize it, if necessary
        if (! array_key_exists('midcom_session_data', $_SESSION))
        {
            $_SESSION['midcom_session_data'] = Array();
            $_SESSION['midcom_session_data']['midcom.service.sessioning']['startup'] = serialize(time());
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
    function exists($domain, $key)
    {
        if (!$this->_initialize())
        {
            return false;
        }
        if (!isset($_SESSION['midcom_session_data'][$domain]))
        {
            debug_add("Request for the domain '{$domain}' failed, because the domain doesn't exist.");
            return false;
        }

        if (!isset($_SESSION['midcom_session_data'][$domain][$key]))
        {
            return false;
        }

        return true;
    }

    /**
     * This is a small, internal helper function, which will load, unserialize and
     * return a given key's value. It is shared by get and remove.
     *
     * @param string $domain    The domain in which to search for the key.
     * @param mixed $key        The key to query.
     * @return mixed            The session key's data value, or NULL on failure.
     */
    function _get_helper ($domain, $key)
    {
        return unserialize($_SESSION["midcom_session_data"][$domain][$key]);
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
     * @return mixed            The session key's data value, or NULL on failure.
     * @see midcom_services__sessioning::exists()
     */
    function get ($domain, $key)
    {
        static $no_cache = false;
        if ($this->exists($domain, $key))
        {
            if (!$no_cache)
            {
                $_MIDCOM->cache->content->no_cache();
                $no_cache = true;
            }
            return $this->_get_helper($domain, $key);
        }
        else
        {
            debug_add("Request for the key '{$key}' in the domain '{$domain}' failed, because the key doesn't exist.");
            return null;
        }
    }

    /**
     * Removes the value associated with the specified key. Returns null if the key
     * is non-existent or the value of the key just removed otherwise. Note, that
     * this is not necessarily a valid non-existence check, as the sessioning
     * system does allow null values. Use the exists function if unsure.
     *
     * @param string $domain    The domain in which to search for the key.
     * @param mixed $key        The key to remove.
     * @return mixed            The session key's data value, or NULL on failure.
     * @see midcom_services__sessioning::exists()
     */
    function remove ($domain, $key)
    {
        if ($this->exists($domain, $key))
        {
            $data = $this->_get_helper($domain, $key);
            unset($_SESSION["midcom_session_data"][$domain][$key]);
            return $data;
        }
        else
        {
            return null;
        }
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
    function set ($domain, $key, $value)
    {
        if (!$this->_initialize(true))
        {
            return false;
        }
        static $no_cache = false;
        if (!$no_cache)
        {
            $_MIDCOM->cache->content->no_cache();
            $no_cache = true;
        }
        $_SESSION["midcom_session_data"][$domain][$key] = serialize($value);
    }
    
    /**
     * Get the session data
     * 
     * @access public
     * @param string $domain   Session domain
     * @return Array containing session values
     */
    function get_session_data($domain)
    {
        if (   !isset($_SESSION)
            || !is_array($_SESSION))
        {
            return false;
        }
        
        if (!array_key_exists($domain, $_SESSION['midcom_session_data']))
        {
            return false;
        }
        
        $session = array();
        
        foreach ($_SESSION['midcom_session_data'][$domain] as $key => $serialized_data)
        {
            $data = unserialize($serialized_data);
            $session[$key] = $data;
        }
        
        return $session;
    }
}
?>