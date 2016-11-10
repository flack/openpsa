<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 Data storage base class.
 *
 * It implements the basic interface required for data storage operations. Naturally,
 * only the construction of storage backends is suspect to class specific code, the actual
 * operation should be completely transparent across all storage implementations.
 *
 * See the individual subclasses for details about their operation.
 *
 * @package midcom.helper.datamanager2
 */
abstract class midcom_helper_datamanager2_storage extends midcom_baseclasses_components_purecode
{
    /**
     * A reference to the data schema used for processing.
     *
     * @var midcom_helper_datamanager2_schema
     * @access protected
     */
    var $_schema = null;

    /**
     * This is a reference the storage object used by the subclass implementation.
     * Types <i>may use this reference only for attachment operations and parameter
     * operations related to attachment operations.</i>
     *
     * It can be safely considered that a MidCOM DBA object is available here if the
     * reference is non-null.
     *
     * Since this member is not necessarily populated in all cases, the base API
     * provides callers with a create_temporary_object helper function: It will put
     * a temporary object into $object, so that attachment operations can still
     * be done. The storage object user must take care of the information stored
     * on that object.
     *
     * @var midcom_core_dbaobject
     */
    var $object = null;

    /**
     * Creates the storage interface class, and initializes it to a given data schema.
     * Specific storage implementation subclasses will need to expand this constructor
     * to take care of linking to the right storage object, where applicable.
     *
     * @param midcom_helper_datamanager2_schema $schema The data schema to use for processing.
     */
    public function __construct($schema)
    {
        parent::__construct();

        $this->_schema = $schema;
    }

    /**
     * This function will populate the $object member with a temporary object obtained
     * by the MidCOM temporary object service.
     *
     * The code using this storage instance <b>must</b> take care of transitting this
     * temporary object into future sessions (which means switching to another storage
     * backend at that point).
     *
     * If the storage object is already populated, the method will exit silently.
     *
     * @see midcom_services_tmp
     * @see midcom_core_temporary_object
     */
    function create_temporary_object()
    {
        if ($this->object === null) {
            $this->object = midcom::get()->tmp->create_object();
        }
    }

    /**
     * Stores a set of types to the configured storage object. This is done
     * by subclass implementations, where this function serves as a request
     * switch.
     *
     * Any types defined in the schema but not found in the passed type listing
     * are ignored unless they are flagged as required, in which case
     * midcom_error is called.
     *
     * @param array &$types A reference to an array of types matching the schema definition.
     * @return boolean Indicating success.
     */
    function store(array &$types)
    {
        foreach ($this->_schema->fields as $name => $type_definition) {
            if (empty($types[$name])) {
                if ($type_definition['required'] == true) {
                    throw new midcom_error("Failed to process the type array for the schema {$this->_schema->name}:
                        The type for the required field {$name} was not found.");
                }
                continue;
            }

            if ($type_definition['readonly']) {
                // Skip storage as we may raise exceptions if the field doesn't exist
                continue;
            }

            // Convert_to_storage is called always, the event handler can be used to manage
            // non-storage-backend driven storage operations as well (mainly for the blob type)
            $data = $types[$name]->convert_to_storage();
            if ($type_definition['storage']['location'] !== null) {
                if ($types[$name]->serialized_storage) {
                    $data = serialize($data);
                }
                $this->_on_store_data($name, $data);
            }
        }

        // Update the storage object last
        if (!$this->_on_update_object()) {
            debug_add('Failed to update the content object, last Midgard Error was: ' . midcom_connection::get_error_string(), MIDCOM_LOG_WARN);
            return false;
        }

        return true;
    }

    /**
     * Implement the storage method for your backend here.
     * It has to store the given data to the schema field identified by the given
     * name.
     *
     * @param string $name The name of the field to save to.
     * @param mixed $data The data to save to.
     */
    abstract function _on_store_data($name, $data);

    /**
     * Loads a set of types to the configured storage object. This is done
     * by subclass implementations, where this function serves as a request
     * switch.
     *
     * Any types defined in the schema but not found in the passed type listing
     * are ignored unless they are flagged as required, in which case
     * midcom_error is thrown.
     *
     * @param array &$types A reference to an array of types matching the schema definition.
     */
    function load(array &$types)
    {
        foreach ($this->_schema->fields as $name => $type_definition) {
            if (!array_key_exists($name, $types)) {
                if ($type_definition['required'] == true) {
                    throw new midcom_error("Failed to process the type array for the schema {$this->_schema->name}:
                        The type for the required field {$name} was not found.");
                }
                continue;
            }
            if ($type_definition['storage']['location'] !== null) {
                $data = $this->_on_load_data($name);
                if ($types[$name]->serialized_storage
                    && is_string($data)) {
                    // Hide unserialization errors, but log them.
                    $data = @unserialize($data);
                    if (!$data) {
                        debug_add("Unserialization failed for field {$name}", MIDCOM_LOG_INFO);
                        midcom::get()->debug->log_php_error(MIDCOM_LOG_INFO);
                    }
                }
            } else {
                $data = null;
                if (    isset($this->_defaults)
                     && array_key_exists($name, $this->_defaults)) {
                    $data = $this->_defaults[$name];
                }
            }
            // Convert_from_storage is called always, the event handler can be used to manage
            // non-storage-backend driven storage operations as well (mainly for the blob type)
            $types[$name]->convert_from_storage($data);
        }
    }

    /**
     * Implement the storage method for your backend here.
     * It has to store the given data to the schema field identified by the given
     * name.
     *
     * @param string $name The name of the field to load from.
     * @return mixed $data The data which has been loaded.
     */
    abstract function _on_load_data($name);

    /**
     * This callback is invoked once the storage object has been completely updated with
     * the information from all types. You need to store it to the database at this point.
     *
     * @return boolean Indicating success.
     */
    abstract function _on_update_object();

    /**
     * Checks whether the current user has the given privilege on the storage backend.
     * If there is no valid storage backend, a can_user_do is performed. Subclasses
     * may overwrite this method to incorporate for creation mode stuff.
     *
     * @param string $privilege The privilege to check against.
     * @return boolean true if the user has the permission, false otherwise.
     */
    function can_do($privilege)
    {
        if ($this->object === null) {
            return midcom::get()->auth->can_user_do($privilege);
        }
        return $this->object->can_do($privilege);
    }
}
