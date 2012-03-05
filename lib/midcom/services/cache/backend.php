<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This is the base class of the MidCOM Cache backend infrastructure. It provides a general
 * interface for the caching services by encapsulating the calls specific to the data
 * storage interface.
 *
 * Each cache database in use is encapsulated by its own instance of this service, identified
 * by their name and the handler which controls it. The name must be unique throughout the
 * entire server. See Namespacing below for details.
 *
 * A handlers type is identified by its class name, so midcom_sercices_cache_backend_dba is
 * the DBA handler from the original pre 2.4 MidCOM.
 *
 * <b>Inter-Process synchronization:</b>
 *
 * Backend drivers have to ensure they can be accessed concurrently to ensure Database
 * integrity.
 *
 * <b>Resource handles:</b>
 *
 * Resource handles (for example for DBA access) should be closed if necessary if they
 * would block other processes. It is unknown to me if such handles would be safe to use
 * over several requests.
 *
 * <b>Namespacing:</b>
 *
 * Each cache database in use has a name, which must consist only of characters valid for
 * file names on the current system. You may create any file or directory within the midcom
 * cache directory as long as you use your name as a prefix.
 *
 * If you want to stay on the safe side, only cache names using the characters matching the
 * regex class [a-zA-Z0-9._-] should be used.
 *
 * <b>General configuration directives:</b>
 *
 * - <i>string directory</i>: The subdirectory in the cache's base directory to use by this
 *   backend. This is automatically concatenated with the systemwide cache base directory.
 * - <i>string driver</i>: The concrete class instance to create. It must match the name of
 *   a file within the backend directory. Note, that the class must also be named accordingly,
 *   the driver "dba" is searched in the file "backend/dba.php" and must be of the type
 *   "midcom_services_cache_backend_dba".
 * - <i>boolean auto_serialize</i>: Set this to true to enable automatic serialization on storage
 *   and/or retrieval. Disabled by default.
 *
 * @package midcom.services
 */

abstract class midcom_services_cache_backend
{
    /**#@+
     * Configuration variable
     *
     * @access protected
     */

    /**
     * The backend instance name. This variable my not be written to
     * after the instance has been created.
     *
     * @var string
     */
    var $_name = null;

    /**
     * Configuration key array. This is populated during initialize().
     *
     * @var Array
     */
    var $_config = null;

    /**
     * The base directory in which we may add files and directories within our namespace.
     *
     * @var string
     */
    var $_cache_dir = null;

    /**
     * Set this to true if you plan to store PHP data structures rather then strings, the
     * interface will automatically serialize/unserialize the data you store/retrieve
     * from the database.
     *
     * @var boolean
     */
    var $_auto_serialize = false;

    /**#@-*/

    /**#@+
     * Internal state variable
     */

    /**
     * True, if the database has been opened for reading previously. This is also
     * true, if we are in read-write mode, naturally.
     *
     * Therefore, this flag is also used for checking whether the database is open
     * in general.
     *
     * @var boolean
     */
    private $_open_for_reading = false;

    /**
     * True, if the database has been opened for writing previously.
     *
     * @var boolean
     */
    private $_open_for_writing = false;

    /**
     * cache of objects, which were already requested from backend.
     *
     * @var array
     */
    private $_local_cache = array();

    /**
     * array of ('key' => true) pairs, listing keys, which have to be synced with
     * backend-implementation
     *
     * @var array
     */
    private $_unsynced_keys = array();

    /**#@-*/

    /**
     * Initializes the backend by acquiring all necessary information required for
     * runtime.
     *
     * After base class initialization, the event handler _on_initialize is called,
     * in which all backend specific stuff should be done.
     *
     * @param string $name The name ("identifier") of the handler instance.
     * @param Array $config The configuration to use.
     */
    function initialize($name, $config)
    {
        $this->_name = $name;
        if (is_array($config))
        {
            $this->_config = $config;
        }
        else
        {
            $this->_config = Array($config);
        }
        if (!isset($this->_config['directory']))
        {
            $this->_config['directory'] = '';
        }
        if (isset($this->_config['auto_serialize']))
        {
            $this->_auto_serialize = $this->_config['auto_serialize'];
        }

        $this->_cache_dir = "{$GLOBALS['midcom_config']['cache_base_directory']}{$this->_config['directory']}";
        $this->_check_cache_dir();

        $this->_on_initialize();
    }

    /**
     * Shutdown the backend. This calls the corresponding event.
     */
    function shutdown()
    {
        $this->flush_unsynced();
        $this->_on_shutdown();
    }

    /**
     * flush all unsynced changes to backend
     */
    public function flush_unsynced()
    {
        if (count($this->_unsynced_keys) === 0)
        {
            return;
        }
        // open
        if (!$this->_open_for_writing)
        {
            $auto_close = true;
            $this->open(true);
        }
        else
        {
            $auto_close = false;
        }

        // sync
        foreach ($this->_unsynced_keys as $key => $_ignore)
        {
            if (array_key_exists($key, $this->_local_cache))
            {
                if ($this->_auto_serialize)
                {
                    $data = serialize($this->_local_cache[$key]);
                }
                else
                {
                    $data = $this->_local_cache[$key];
                }

                $this->_put($key, $data);
            }
            else
            {
                $this->_remove($key);
            }
        }
        $this->_unsynced_keys = array();

        // close
        if ($auto_close === true)
        {
            $this->close();
        }
    }

    /**
     * This helper will ensure that the cache base directory is created and usable
     * by checking it is actually a directory. If it does not exist, it will be created
     * automatically. Errors will be handled by midcom_error.
     */
    private function _check_cache_dir()
    {
        if (   !file_exists($GLOBALS['midcom_config']['cache_base_directory'])
            && !@mkdir($GLOBALS['midcom_config']['cache_base_directory'], 0755))
        {
            throw new midcom_error("Failed to create the cache base directory {$this->_cache_dir}: {$php_errormsg}");
        }

        if (!file_exists($this->_cache_dir))
        {
            if (!@mkdir($this->_cache_dir, 0755))
            {
                throw new midcom_error("Failed to create the cache base directory {$this->_cache_dir}: {$php_errormsg}");
            }
        }
        else if (!is_dir($this->_cache_dir))
        {
            throw new midcom_error("Failed to create the cache base directory {$this->_cache_dir}: A file of the same name already exists.");
        }
    }

    /**#@+
     * Event handler function.
     */

    /**
     * Backend initialization
     *
     * Add any custom startup code here. The configuration variables are
     * all initialized when this handler is called.
     */
    public function _on_initialize() {}

    /**
     * Backend shutdown
     *
     * Called, if the backend is no longer used.
     */
    public function _on_shutdown() {}

    /**#@-*/

    /**
     * Open the database for usage. If $write is set to true, it must be opened in
     * read/write access, otherwise read-only access is sufficient.
     *
     * If the database cannot be opened, midcom_error should
     * be thrown.
     *
     * The concrete subclass must track any resource handles internally, of course.
     *
     * @param boolean $write True, if read/write access is required.
     */
    abstract function _open($write = false);

    /**
     * Close the database that has been opened previously with _open().
     */
    abstract function _close();

    /**
     * Get the data associated with the given key.
     *
     * The data store is opened either read-only or read-write when this
     * function executes.
     *
     * @param string $key Key to look up.
     * @return string $data The data associated with the key.
     */
    abstract function _get($key);

    /**
     * Checks, whether the given key exists in the Database.
     *
     * The data store is opened either read-only or read-write when this
     * function executes.
     *
     * @param string $key The key to check for.
     * @return boolean Indicating existence.
     */
    abstract function _exists($key);

    /**
     * Store the given key/value pair, any existing entry with the same
     * key has to be silently overwritten.
     *
     * The data store is opened in read-write mode when this function executes.
     *
     * Any error condition should throw midcom_error and
     * must close the data store before doing so.
     *
     * @param string $key The key to store at.
     * @param string $data The data to store.
     */
    abstract function _put($key, $data);

    /**
     * Delete the data with the given key from the database.
     *
     * The data store is opened in read-write mode when this function executes.
     *
     * Deleting non existent keys
     * should fail silently. All other error conditions should throw
     * midcom_error and must close the data store before doing so.
     *
     * @param string $key The key to delete.
     */
    abstract function _remove($key);

    /**
     * Drops the entire database, preferably with some kind of truncate operation.
     *
     * The data store will not be opened in either read-only or read-write mode when
     * this function executes, to allow for open/truncate operations.
     *
     * Any error condition should throw midcom_error
     */
    abstract function _remove_all();

    /**
     * Open the database for usage. If $write is set to true, it must be opened in
     * read/write access, otherwise read-only access is sufficient.
     *
     * If the database is reopened with different access permissions then currently
     * specified (e.g. if going from read-only to read-write), the database is closed
     * prior to opening it again. If the permissions match the current state, nothing
     * is done.
     *
     * @param boolean $write True, if read/write access is required.
     */
    function open($write = false)
    {
        // Check, whether the DB is already open.
        if ($this->_open_for_reading)
        {
            // Check whether the access permissions are correct, if yes, we ignore the
            // open request, otherwise we close the db.
            if ($this->_open_for_writing == $write)
            {
                debug_add("The database has already been opened with the requested permission, ignoring request.");
                return;
            }

            // Close the db
            $this->_close();
        }

        $this->_open($write);
        $this->_open_for_reading = true;
        $this->_open_for_writing = (bool) $write;
    }

    /**
     * Close the database that has been opened previously with open(). If the database
     * is already closed, the request is ignored silently.
     */
    function close()
    {
        if (! $this->_open_for_reading)
        {
            debug_add("The database is not open, ignoring the request to close the database.");
            return;
        }

        $this->_close();
        $this->_open_for_reading = false;
        $this->_open_for_writing = false;
    }

    /**
     * Checks, whether the given key exists in the Database. If the data store has not yet
     * been opened for reading, it will be opened automatically prior to the call,
     * and closed automatically again afterwards.
     *
     * @param string $key The key to check for.
     * @return boolean Indicating existence.
     */
    function exists($key)
    {
        // Ensure key is of valid type
        if (!is_string($key))
        {
            return false;
        }

        // checking local-cache
        if (isset($this->_local_cache[$key]))
        {
            return true;
        }
        elseif (isset($this->_unsynced_keys[$key]))
        {
            // this is the deleted key
            return false;
        }

        if (! $this->_open_for_reading)
        {
            $auto_close = true;
            $this->open(false);
        }
        else
        {
            $auto_close = false;
        }

        $result = $this->_exists($key);

        if ($auto_close)
        {
            $this->close();
        }

        return $result;
    }

    /**
     * Get the data associated with the given key. If the data store has not yet
     * been opened for reading, it will be opened automatically prior to the call,
     * and closed automatically again afterwards.
     *
     * @param string $key Key to look up.
     * @return string $data The data associated with the key.
     */
    function get($key)
    {
        // checking local-cache
        if (isset($this->_local_cache[$key]))
        {
            return $this->_local_cache[$key];
        }
        elseif (isset($this->_unsynced_keys[$key]))
        {
            // this is the deleted key
            return false;
        }

        if (! $this->_open_for_reading)
        {
            $auto_close = true;
            $this->open(false);
        }
        else
        {
            $auto_close = false;
        }

        $result = $this->_get($key);

        if ($auto_close)
        {
            $this->close();
        }

        if (   $this->_auto_serialize
            && is_string($result))
        {
            try
            {
                // FIXME: The @ is here because Midgard causes Warnings on object constructor not being called on unserialize
                $result = @unserialize($result);
            }
            catch (Exception $e)
            {
                return;
            }
        }

        if (false !== $result)
        {
            // do not cache false-results. those are considered to be "errors"
            $this->_local_cache[$key] = $result;
        }

        return $result;
    }

    /**
     * Store the given key/value pair, any existing entry with the same
     * key has to be silently overwritten. If the data store has not yet been
     * opened for writing, it will be opened automatically prior to the call,
     * and closed automatically again afterwards.
     *
     * @param string $key The key to store at.
     * @param string $data The data to store.
     */
    function put($key, $data)
    {
        if ($data === false or $data === null)
        {
            // do not cache false-results. those are considered to be "errors"
            return;
        }

        $this->_local_cache[$key] = $data;
        $this->_unsynced_keys[$key] = true;
    }

    /**
     * Delete the data with the given key from the database. Deleting non existent keys
     * should fail silently. If the data store has not yet been
     * opened for writing, it will be opened automatically prior to the call,
     * and closed automatically again afterwards.
     *
     * @param string $key The key to delete.
     */
    function remove($key)
    {
        unset($this->_local_cache[$key]);
        $this->_unsynced_keys[$key] = true;
    }

    /**
     * Drops the entire database and creates an empty one.
     *
     * The database must not be opened by this process when this is called. If it is,
     * it will be automatically closed prior to executing this call.
     *
     * Any error condition should throw midcom_error.
     */
    function remove_all()
    {
        if ($this->_open_for_reading)
        {
            $this->close();
        }

        $this->_remove_all();
        $this->_local_cache = array(); // reset local-cache
        $this->_unsynced_keys = array();
    }
}
