<?php
/**
 * @package midcom.helper
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This class is designed to ease MidCOM Configuration management.
 *
 * Basically it supports key/value pairs of data, which can be retrieved out of Midgard
 * Parameters. In this case it would make the key/values a string/string pair with
 * a length limit of 255 characters. Since the current implementation only supports
 * read-access to the configuration data, this is a negligible fact, in reality it
 * supports all valid PHP data types as key or data values, as long it is allowed
 * to use the keys as array index elements.
 *
 * This class is designed to manage parameter like key/value configuration data.
 * The class makes no assumption about the value type of keys or values, any valid
 * PHP data type is allowed. Two different sets of configuration options are stored
 * within the class, the "global" and the "local" configuration.
 *
 * The global configuration must include all possible configuration parameters with
 * their default values. These data is fixed and cannot be changed after object
 * instantiation. Aimed specifically at MidCOM is the second set of configuration
 * data, the "local" parameters. It gives you a way of explicitly overwrite a part
 * of the configuration data with localized values. This customization data can be
 * overwritten at wish by deliberately resetting it to the defaults or by importing
 * a new one over the existing local configuration.
 *
 * Configuration data can be delivered in two ways: The easiest way is using a
 * associative array that will be used as configuration. Alternatively you can
 * specify both a MidgardObject and a MidCOM Path which is used to fetch
 * configuration data.
 *
 * Any configuration key in the local configuration, which is not present in the
 * global "template", will be logged as a warning. This should normally not happen.
 * Originally, this case threw a critical error, but that made upgrading
 * configurations quite difficult.
 *
 * @package midcom.helper
 */
class midcom_helper_configuration
{
    /**
     * Globally assigned configuration data.
     *
     * @var array
     */
    public $_global = [];

    /**
     * Locally overridden configuration data.
     *
     * @var array
     */
    public $_local = [];

    /**
     * Merged, current configuration state.
     *
     * @var array
     */
    private $_merged = [];

    /**
     * Internal cache-related items
     * @ignore
     */
    private $_object;
    private $_path;

    /**
     * The constructor initializes the global configuration.
     *
     * Two sources can be specified:
     *
     * First, if passed a single associative array to the constructor,
     * it will use its contents as global configuration.
     *
     * Alternatively you can specify any Midgard object and a parameter
     * domain. It will then use the contents of this domain as global
     * configuration.
     *
     * @param mixed $param1        Either an associative array or a Midgard object.
     * @param mixed $param2        Either null or the name of a Parameter domain.
     */
    public function __construct($param1, $param2 = null)
    {
        if ($param2 !== null) {
            $this->_object = $param1;
            $this->_path = $param2;
            $this->_store_from_object(true);
        } elseif ($param1 !== null) {
            $this->_global = $param1;
            $this->_merged = $param1;
        }
    }

    /**
     * Fetch the configuration data stored in the parameter domain _path of _object.
     *
     * The flag $global controls whether the global or the local configuration should
     * be updated. No control whether an update of the global data is allowed is done
     * here, the caller has to do this.
     * This function will update the config data cache array. If it stores global
     * configuration data it will automatically erase the local configuration data.
     *
     * Any error such as invalid configuration data will trigger a MidCOM error.
     *
     * @param boolean            $global        Set to true to replace the global configuration.
     */
    private function _store_from_object(bool $global = false, bool $merge = false)
    {
        // Cast to DBA type.
        if (!midcom::get()->dbclassloader->is_midcom_db_object($this->_object)) {
            $this->_object = midcom::get()->dbfactory->convert_midgard_to_midcom($this->_object);
        }

        $array = $this->_object->list_parameters($this->_path);

        if ($global) {
            $this->_global = ($merge) ? array_merge($this->_global, $array) : $array;
            $this->_local = [];
            $this->_merged = $array;
        }

        $this->_check_local_array($array);
        $this->_local = ($merge) ? array_merge($this->_local, $array) : $array;
        $this->_update_cache();
    }

    /**
     * Merge the local and the global configuration arrays into the cache array.
     */
    private function _update_cache()
    {
        $this->_merged = $this->_global;
        if (!empty($this->_local)) {
            $this->_merged = array_merge($this->_merged, $this->_local);
        }
    }

    /**
     * Check local data array for validity
     *
     * Since the local array must only include configuration parameters that are
     * included in the global configuration, this function is used to check a local
     * array against the current global configuration. true/false is returned
     * accordingly.
     */
    private function _check_local_array(array $array)
    {
        $diff = array_keys(array_diff_key($array, $this->_global));
        foreach ($diff as $key) {
            debug_add("The key {$key} is not present in the global configuration array.", MIDCOM_LOG_INFO);
        }
    }

    /**
     * Write the parameters in $params into the local configuration.
     *
     * If $reset is set, the local configuration will be cleared before
     * the new set is imported, if not, the new data is merged with the old local
     * configuration, overwriting duplicates. During import each configuration key will
     * be checked against the global configuration values. If an unknown value is found,
     * import will be aborted and no changes to the configuration is done.
     *
     * After import the cache array will be updated, reset is done by reset_local.
     *
     * @param boolean    $reset        If set to true, the current local configuration will be discarded first.
     * @see midcom_helper_configuration::reset_local()
     */
    public function store(array $params, bool $reset = true)
    {
        $this->_check_local_array($params);
        if ($reset) {
            $this->reset_local();
        }
        $this->_local = array_merge($this->_local, $params);
        $this->_update_cache();
    }

    /**
     * Import data from a Midgard object.
     *
     * To import configuration data from a Midgard Object, use this method. As in the
     * respective constructor it will retrieve the configuration data in the parameter
     * domain $path of $object. Unlike the constructor this function will store the
     * data in the local configuration.
     *
     * @param MidgardObject $object    The object from which to import data.
     * @param string $path    The parameter domain to query.
     * @param boolean $merge Should the existing local config be overridden or merged
     */
    public function store_from_object(object $object, string $path, bool $merge = false)
    {
        $this->_object = $object;
        $this->_path = $path;
        $this->_store_from_object(false, $merge);
    }

    /**
     * Clear the local configuration data, effectively reverting to the global
     * default.
     */
    public function reset_local()
    {
        $this->_local = [];
        $this->_merged = $this->_global;
    }

    /**
     * Retrieve a configuration key
     *
     * If $key exists in the configuration data, its value is returned to the caller.
     * If the value does not exist, the boolean value false will be returned. Be aware
     * that this is not always good for error checking, since "false" is a perfectly good
     * value in the configuration data. Do error checking with the function exists (see
     * below).
     *
     * @return mixed        Its value or false, if the key doesn't exist.
     * @see midcom_helper_configuration::exists()
     */
    public function get(string $key)
    {
        if ($this->exists($key)) {
            return $this->_merged[$key];
        }
        return false;
    }

    public function get_array(string $key) : array
    {
        if ($value = $this->get($key)) {
            if (!is_array($value)) {
                throw new midcom_error('Config key "' . $key . '" is not an array');
            }
            return $value;
        }
        return [];
    }

    /**
     * Set a value on the current instance, if the given key exists
     */
    public function set(string $key, $value)
    {
        if ($this->exists($key)) {
            $this->_local[$key] = $value;
            $this->_update_cache();
        }
    }

    /**
     * Retrieve a copy the complete configuration array.
     */
    public function get_all() : array
    {
        return $this->_merged;
    }

    /**
     * Checks for the existence of a configuration key.
     */
    public function exists(string $key) : bool
    {
        return array_key_exists($key, $this->_merged);
    }
}
