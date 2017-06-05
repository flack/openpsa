<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 Data Type base class.
 *
 * As with all subclasses, the actual initialization is done in the initialize() function.
 *
 * <b>Type configuration:</b>
 *
 * - Now uses class members, which should use initializers (var $name = 'default_value';)
 *   for configuration defaults.
 * - The schema configuration ('type_config') is merged using the semantics
 *   $type->$key = $value;
 *
 * @package midcom.helper.datamanager2
 */
abstract class midcom_helper_datamanager2_type extends midcom_baseclasses_components_purecode
{
    /**
     * Reference to the datamanager instance this type belongs to
     *
     * @var midcom_helper_datamanager2_datamanager
     */
    protected $_datamanager = null;

    /**
     * Set this to true during one of the startup callbacks if you need to have the
     * datastorage (de)serialized automatically during I/O operations.
     *
     * @var boolean
     */
    var $serialized_storage = false;

    /**
     * This field contains the reason for the failed validation.
     *
     * The string can be safely assumed to be localized, and is only valid if a
     * validation has failed previously. This field will be cleared prior to a
     * new validation attempt. You may use simple inline HTML in these errors.
     *
     * @var string
     */
    var $validation_error = '';

    /**
     * This variable contains configuration data which is not directly related to the
     * operation of the type, but required for the operation of external tools like the
     * storage manager.
     *
     * The type should never touch this variable, which is controlled
     * by a corresponding getter/setter pair.
     *
     * @var Array
     * @see set_external_config()
     * @see get_external_config()
     */
    private $_external_config = [];

    /**
     * The name field holds the name of the field the datatype is encapsulating.
     *
     * This maps to the schema's field name. You should never have to change them.
     *
     * @var string
     */
    var $name = '';

    /**
     * A reference to the storage object that this type is using.
     *
     * Use this for attachment management. The variable may be null until
     * actual processing starts. It may also change during the lifetime
     * of a type. You should therefore be careful.
     *
     * @var midcom_helper_datamanager2_storage
     * @access protected
     */
    var $storage = null;

    /**
     * Initializes and configures the type.
     *
     * @param string $name The name of the field to which this type is bound.
     * @param array $config The configuration data which should be used to customize the type.
     * @param midcom_helper_datamanager2_storage &$storage A reference to the storage object to use.
     * @param midcom_helper_datamanager2_datamanager $datamanager The current DM2 instance
     */
    function initialize($name, $config, $storage, midcom_helper_datamanager2_datamanager $datamanager)
    {
        $this->_datamanager = $datamanager;
        $this->name = $name;
        $this->set_storage($storage);

        // Call the event handler for configuration in case we have some defaults that cannot
        // be covered by the class initializers.
        $this->_on_configuring($config);

        // Assign the configuration values.
        foreach ($config as $key => $value) {
            $this->$key = $value;
        }

        $this->_on_initialize();
    }

    /**
     * Prepares the option callback
     *
     * @return midcom_helper_datamanager2_callback_interface The prepared callback object
     */
    public function initialize_option_callback()
    {
        $classname = $this->option_callback;

        if (!class_exists($classname)) {
            throw new midcom_error("Auto-loading of the class {$classname} failed: File does not exist.");
        }

        $callback = new $classname($this->option_callback_arg);
        if (!($callback instanceof midcom_helper_datamanager2_callback_interface)) {
            throw new midcom_error($classname . ' must implement interface midcom_helper_datamanager2_callback_interface');
        }

        $callback->set_type($this);

        if (is_callable([$callback, 'initialize'])) {
            $callback->initialize();
        }

        return $callback;
    }

    /**
     * This function, is called  before the configuration keys are merged into the types
     * configuration.
     *
     * @param array $config The configuration passed to the type.
     */
    public function _on_configuring($config)
    {
    }

    /**
     * Set the current storage object to a new one
     *
     * @param midcom_helper_datamanager2_storage $storage A reference to the storage object to use.
     */
    function set_storage($storage)
    {
        $this->storage = $storage;
    }

    /**
     * This event handler is called after construction, so passing references to $this to the
     * outside is safe at this point.
     */
    public function _on_initialize()
    {
    }

    /**
     * Converts from storage format to "operational" format, which might include
     * more information than the pure storage format.
     *
     * Depending on the $serialized_storage member, the framework will
     * automatically deal with deserialization of the information.
     *
     * @param mixed $source The storage data structure.
     */
    abstract public function convert_from_storage($source);

    /**
     * Converts from "operational" format to from storage format.
     *
     * Depending on the $serialized_storage member, the framework will
     * automatically deal with deserialization of the information.
     *
     * @return mixed The data to store into the object, or null on failure.
     */
    abstract public function convert_to_storage();

    /**
     * Constructs the object based on its CSV representation (which is already decoded in terms
     * of escaping.)
     *
     * @param string $source The CSV representation that has to be parsed.
     */
    abstract public function convert_from_csv($source);

    /**
     * Transforms the current object's state into a CSV string representation.
     *
     * Escaping and other encoding is done by the caller, you just return the string.
     *
     * @return mixed The data to store into the object, or null on failure.
     */
    abstract public function convert_to_csv();

    /**
     * Transforms the current object's state into a email-friendly string representation.
     *
     * Escaping and other encoding is done by the caller, you just return the string.
     *
     * If this method is not overwritten, convert_to_csv will be used instead.
     *
     * @return mixed The data to store into the object, or null on failure.
     */
    public function convert_to_email()
    {
        return $this->convert_to_csv();
    }

    /**
     * Transforms the current object's state into HTML representation.
     *
     * This is used for displaying type contents in an automatic fashion.
     *
     * @return mixed The rendered content.
     */
    abstract public function convert_to_html();

    /**
     * Transforms the current objects' state into 'raw' representation.
     * Usually the convert_to_storage -method returns suitable value, but in
     * some datatypes (like privilege, blobs-based ones and tags),
     * convert_to_storage does database IO directly and returns less useful data.
     *
     * @see convert_to_storage
     * @return mixed The rendered content.
     */
    public function convert_to_raw()
    {
        return $this->convert_to_storage();
    }

    /**
     * Main validation interface, currently only calls the main type callback, but this
     * can be extended later by a configurable callback into the component.
     *
     * @return boolean Indicating value validity.
     */
    function validate()
    {
        $this->validation_error = '';
        return $this->_on_validate();
    }

    /**
     * Type-specific validation callback, this is executed before any custom validation
     * rules which apply through the customization interface.
     *
     * In case validation fails, you should assign an (already translated) error message
     * to the validation_error public member.
     *
     * @return boolean Indicating value validity.
     */
    public function _on_validate()
    {
        return true;
    }

    /**
     * This is a shortcut to the translate_schema_string function.
     *
     * @param string $string The string to be translated.
     * @return string The translated string.
     * @see midcom_helper_datamanager2_schema::translate_schema_string()
     */
    public function translate($string)
    {
        return $this->_datamanager->schema->translate_schema_string($string);
    }

    /**
     * Gets an external configuration option referenced by its key.
     *
     * Besides other parts in the datamanager framework, nobody should ever
     * have to touch this information.
     *
     * @param string $key The key by which this configuration option is referenced.
     * @return mixed The configuration value, which is null if the key wasn't found.
     */
    function get_external_config($key)
    {
        if (!array_key_exists($key, $this->_external_config)) {
            return null;
        }
        return $this->_external_config[$key];
    }

    /**
     * Sets an external configuration option.
     *
     * Besides other parts in the datamanager framework, nobody should ever
     * have to touch this information.
     *
     * @param string $key The key by which this configuration option is referenced.
     * @param mixed $value The configuration value.
     */
    function set_external_config($key, $value)
    {
        $this->_external_config[$key] = $value;
    }

    /**
     * Checks whether the current user has the given privilege on the storage backend.
     *
     * The storage backend is responsible for the actual execution of this operation,
     * so this is merely a shortcut.
     *
     * @param string $privilege The privilege to check against.
     * @return boolean true if the user has the permission, false otherwise.
     */
    function can_do($privilege)
    {
        return $this->storage->can_do($privilege);
    }
}
