<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 Data Manager core class.
 *
 * This class controls all type I/O operations, including entering and exiting
 * editing operations and creation support. It brings Types, Schemas and Storage objects
 * together.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_datamanager extends midcom_baseclasses_components_purecode
{
    /**
     * The schema database to use for operation. This variable will always contain a parsed
     * representation of the schema, so that one can swiftly switch between individual schemas
     * of the Database. This is a list of midcom_helper_datamanager2_schema
     * instances, indexed by their name.
     *
     * @var Array
     */
    private $_schemadb = null;

    /**
     * This variable holds the schema currently in use, it has been created from the array
     * stored in the $_schemadb member.
     *
     * This object can be modified as long as the types are not initialized. If you change
     * the schema afterwards, the changes will not propagate to any dependant object until
     * you reinitialize the class.
     *
     * @var midcom_helper_datamanager2_schema
     */
    public $schema = null;

    /**
     * The id (array index) of the current schema
     * @var string schema_name
     */
    var $schema_name = '';

    /**
     * This is the storage implementation which is used for operation on the types. It encapsulates
     * the storage target.
     *
     * @var midcom_helper_datamanager2_storage
     */
    public $storage = null;

    /**
     * This is a listing of all types that have been loaded from the storage object. You may
     * manipulate these types and their values at will, and then store them back to the database
     * using the functions available in this class.
     *
     * @var Array
     */
    var $types = null;

    /**
     * This variable contains an Array of all validation errors that have occurred
     * during saving. As outlined in the type, these messages my have inline-html
     * in it and it is assumed to be localized.
     *
     * The errors are indexed by field name.
     *
     * @var Array
     * @see midcom_helper_datamanager2_type::$validation_error
     */
    var $validation_errors = [];

    /**
     * Reference to the form manager instance which is currently in use. Usually, it is created and referenced here by the controller
     * class during initialization.
     *
     * @var midcom_helper_datamanager2_formmanager
     */
    var $formmanager = null;

    /**
     * The constructor loads the schema database to use but does nothing else
     * so far.
     *
     * @param array &$schemadb A list of midcom_helper_datamanager2_schema instances,
     *     indexed by their schema name. This member is taken by reference.
     * @see midcom_helper_datamanager2_schema::load_database()
     */
    public function __construct(array &$schemadb)
    {
        parent::__construct();
        $this->_schemadb =& $schemadb;
    }

    /**
     * This function activates the given schema. This will drop all existing types
     * and create a new set of them which are in the default state at this point.
     *
     * This will reset the existing schema and type listing. If a storage object
     * exists, the change of the schema will be propagated implicitly, as it will
     * reference the schema member of ours.
     *
     * @param string $name The name of the schema to use, omit this to use the default
     *     schema.
     * @return boolean Indicating success.
     */
    function set_schema($name = null)
    {
        if ($name === null) {
            reset($this->_schemadb);
            $name = key($this->_schemadb);
        }
        if (!array_key_exists($name, $this->_schemadb)) {
            debug_add("The schema {$name} was not found in the active schema database.", MIDCOM_LOG_INFO);
            return false;
        }

        $this->schema =& $this->_schemadb[$name];
        $this->schema_name = $name;

        return $this->_load_types();
    }

    /**
     * This function sets the system to use a specific storage object. You can pass
     * either a MidCOM DBA object or a fully initialized storage subclass. The former
     * is automatically wrapped in a midcom storage object. If you pass your own
     * storage object, ensure that it uses the same schema as this class. Ideally,
     * you should use references for this.
     *
     * This call will fail if there is no schema set. All types will be set and
     * initialized to the new storage object. Thus, it is possible to call set_storage
     * repeatedly thus switching an existing DM instance over to a new storage object
     * as long as you work with the same schema.
     *
     * @param mixed $object A reference to either a MidCOM DBA class or a subclass of
     *     midcom_helper_datamanager2_storage.
     * @return boolean Indicating success.
     */
    function set_storage($object)
    {
        if ($this->schema === null) {
            debug_add('Cannot initialize to a storage object if the schema is not yet set.', MIDCOM_LOG_INFO);
            return false;
        }

        if (!is_a($object, 'midcom_helper_datamanager2_storage')) {
            $this->storage = new midcom_helper_datamanager2_storage_midgard($this->schema, $object);
        } else {
            $this->storage = $object;
        }

        // For reasons I do not completely comprehend, PHP drops the storage references into the types
        // in the lines above. Right now the only solution (except debugging this 5 hours long line
        // by line) I see is explicitly setting the storage references in the types.
        foreach ($this->types as $type) {
            $type->set_storage($this->storage);
        }

        $this->storage->load($this->types);

        return true;
    }

    /**
     * This function will create all type objects for the current schema. It will load class
     * files where necessary (using require_once), and then create a set of instances
     * based on the schema.
     *
     * @return boolean Indicating success
     */
    private function _load_types()
    {
        $this->types = [];

        if (!$this->schema) {
            debug_add("Failed to initialize the types, schema not defined.",
                MIDCOM_LOG_INFO);
            return false;
        }

        foreach ($this->schema->fields as $name => $config) {
            $this->_load_type($name, $config);
        }

        return true;
    }

    private function _load_type($name, $config)
    {
        if (!isset($config['type'])) {
            throw new midcom_error("The field {$name} is missing type");
        }

        $classname = $config['type'];
        if (strpos($classname, '_') === false) {
            // Built-in type called using the shorthand notation
            $classname = "midcom_helper_datamanager2_type_{$config['type']}";
        }

        $this->types[$name] = new $classname();
        if (!$this->types[$name] instanceof midcom_helper_datamanager2_type) {
            throw new midcom_error("{$classname} is not a valid DM2 type");
        }

        if ($this->types[$name]->initialize($name, $config['type_config'], $this->storage, $this) === false) {
            throw new midcom_error("Failed to initialize the type for {$name}");
        }
    }

    /**
     * This function is a shortcut that combines set_schema and set_storage together.
     * The schema name is looked up in the parameter 'midcom.helper.datamanager2/schema_name',
     * if it is not found, the first schema from the schema database is used implicitly.
     *
     * @see set_schema()
     * @see set_storage()
     * @param mixed $object Either a MidCOM DBA class or a subclass of midcom_helper_datamanager2_storage.
     * @param boolean $strict Whether we should strictly use only the schema given by object params
     * @return boolean Indicating success.
     */
    function autoset_storage($object, $strict = false)
    {
        if (is_a($object, 'midcom_helper_datamanager2_storage')) {
            $schema = $object->object->get_parameter('midcom.helper.datamanager2', 'schema_name');
        } else {
            if (!is_object($object)) {
                return false;
            }
            $schema = $object->get_parameter('midcom.helper.datamanager2', 'schema_name');
        }

        if (!$schema) {
            $schema = null;
        }

        if (!$this->set_schema($schema)) {
            if (   $strict
                || $schema == null) {
                return false;
            }
            debug_add("Given schema name {$schema} was not found, reverting to default.", MIDCOM_LOG_INFO);
            // Schema database has probably changed so we should be graceful here
            if (!$this->set_schema(null)) {
                return false;
            }
        }
        return $this->set_storage($object);
    }

    /**
     * This function will cycle through all fields of the loaded schema, and run a "recreate" method for the
     * type, if it has one.
     *
     * This can be used for regenerating all images or converted files of a given object when for instance
     * schema changes.
     *
     * @return boolean
     */
    function recreate()
    {
        $stat = true;
        foreach ($this->types as $type) {
            if (!method_exists($type, 'recreate')) {
                // This type doesn't support recreation
                continue;
            }
            if (!$type->recreate()) {
                $stat = false;
            }
        }

        return $stat;
    }

    /**
     * This function will save the current state of all types to disk. A full
     * validation cycle is done beforehand, if any validation fails, the function
     * aborts and sets the $validation_errors member variable accordingly.
     *
     * @return boolean Indicating success
     */
    function save()
    {
        if (!$this->validate()) {
            debug_add(count($this->validation_errors) . ' fields have failed validation, cannot save.',
                MIDCOM_LOG_WARN);
            debug_print_r('Validation errors:', $this->validation_errors);
            return false;
        }

        return $this->storage->store($this->types);
    }

    /**
     * Validate the current object state. It will populate $validation_errors
     * accordingly.
     *
     * @return boolean Indicating validation success.
     */
    function validate()
    {
        $this->validation_errors = [];
        $validated = true;
        foreach (array_keys($this->schema->fields) as $name) {
            if ($this->_schema_field_is_broken($name)) {
                continue;
            }
            if (!$this->types[$name]->validate()) {
                $this->validation_errors[$name] = $this->types[$name]->validation_error;
                $validated = false;
            }
        }
        return $validated;
    }

    /**
     * Very basic schema sanity checking, raises messages as needed
     *
     * @param string $name field name
     * @return boolean indicating b0rkedness
     */
    function _schema_field_is_broken($name)
    {
        if (isset($this->types[$name])) {
            return false;
        }
        $msg = "DM2->types['{$name}'] is not set (but was present in field_order/fields array), current instance of schema '{$this->schema_name}' is somehow broken";
        midcom::get()->uimessages->add($this->_l10n->get($this->_component), $msg, 'error');
        debug_add($msg, MIDCOM_LOG_ERROR);
        debug_print_r('DM2->schema->field_order', $this->schema->field_order);

        return true;
    }

    /**
     * Little helper function returning an associative array of all field values converted to HTML
     * using their default convert_to_html option.
     *
     * @return Array All field values in their HTML representation indexed by their name.
     */
    function get_content_html()
    {
        if (is_null($this->formmanager)) {
            $this->formmanager = new midcom_helper_datamanager2_formmanager($this->schema, $this->types);
            $this->formmanager->initialize();
        }

        $result = [];
        foreach ($this->schema->field_order as $name) {
            if ($this->_schema_field_is_broken($name)) {
                continue;
            }
            if (empty($this->formmanager->widgets[$name])) {
                //This field seems to be hidden, i.e. DM has not loaded the widget
                $result[$name] = $this->types[$name]->convert_to_html();
            } else {
                $this->formmanager->widgets[$name]->_type = $this->types[$name];
                $result[$name] = $this->formmanager->widgets[$name]->render_content();
            }
        }
        return $result;
    }

    /**
     * Little helper function returning an associative array of all field values converted to XML
     * using their default convert_to_csv or convert_to_raw options.
     *
     * @return Array All field values in their XML representation indexed by their name.
     */
    function get_content_xml()
    {
        $result = [];
        foreach ($this->schema->field_order as $name) {
            if ($this->_schema_field_is_broken($name)) {
                continue;
            }
            if (is_a($this->types[$name], 'midcom_helper_datamanager2_type_blobs')) {
                $result[$name] = explode(',', $this->types[$name]->convert_to_csv());
            } elseif (is_a($this->types[$name], 'midcom_helper_datamanager2_type_select')) {
                $this->types[$name]->csv_export_key = true;
                $this->types[$name]->multiple_storagemode = 'array';
                $result[$name] = $this->types[$name]->convert_to_storage();
            } else {
                $result[$name] = $this->types[$name]->convert_to_storage();
            }
        }
        return $result;
    }

    /**
     * Little helper function returning an associative array of all field values converted to CSV
     * using their default convert_to_csv option.
     *
     * @return Array All field values in their CSV representation indexed by their name.
     */
    function get_content_csv()
    {
        $result = [];
        foreach ($this->schema->field_order as $name) {
            if ($this->_schema_field_is_broken($name)) {
                continue;
            }
            $result[$name] = $this->types[$name]->convert_to_csv();
        }
        return $result;
    }

    /**
     * Little helper function returning an associative array of all field values converted to email-friendly format
     * using their default convert_to_email option.
     *
     * @return Array All field values in their CSV representation indexed by their name.
     */
    function get_content_email()
    {
        $result = [];
        foreach ($this->schema->field_order as $name) {
            if ($this->_schema_field_is_broken($name)) {
                continue;
            }
            $result[$name] = $this->types[$name]->convert_to_email();
        }
        return $result;
    }

    /**
     * Get all field values converted to their raw storage representation
     *
     * @return Array All field values in their raw storage representation indexed by their name.
     */
    function get_content_raw()
    {
        $result = [];
        foreach ($this->schema->field_order as $name) {
            if ($this->_schema_field_is_broken($name)) {
                continue;
            }
            $result[$name] = $this->types[$name]->convert_to_raw();
        }
        return $result;
    }

    /**
     * This function displays a quick view of the record, using some simple div based layout,
     * which can be formatted using CSS.
     *
     * Be aware that this is only geared for simple administration interfaces, it will provide
     * *no* editing capabilities (like AJAX) etc. If you want that to work, you need a formmanger
     * instance instead.
     *
     * @param boolean $skip_empty Should empty fields be rendered or not
     */
    function display_view($skip_empty = false)
    {
        $values = $this->get_content_html();
        // iterate over all types so that they can add their piece to the form
        echo "<div class=\"midcom_helper_datamanager2_view\">\n";
        $fieldset_count = 0;
        foreach ($this->schema->field_order as $name) {
            $config =& $this->schema->fields[$name];
            if (!empty($config['hidden'])) {
                continue;
            }

            if (isset($config['start_fieldset'])) {
                if (isset($config['start_fieldset']['title'])) {
                    $fieldsets = [];
                    $fieldsets[] = $config['start_fieldset'];
                } else {
                    $fieldsets = $config['start_fieldset'];
                }
                foreach ($fieldsets as $fieldset) {
                    if (isset($fieldset['css_group'])) {
                        $class = $fieldset['css_group'];
                    } else {
                        $class = $name;
                    }
                }
                echo "<div class=\"fieldset {$class}\">\n";
                if (isset($fieldset['title'])) {
                    if (isset($fieldset['css_title'])) {
                        $class = " class=\"{$fieldset['css_title']}\"";
                    } else {
                        $class = " class=\"{$name}\"";
                    }

                    echo "    <h2{$class}>\n";
                    echo "        ". $this->schema->translate_schema_string($fieldset['title']) ."\n";
                    echo "    </h2>\n";
                }
                if (isset($fieldset['description'])) {
                    echo "<p>". $this->schema->translate_schema_string($fieldset['description']) . "</p>\n";
                }
                $fieldset_count++;
            }

            $field_value = $values[$name];
            if (   !$skip_empty
                || trim($field_value) !== '') {
                echo "<div class=\"field\">\n";
                echo '<div class="title">' . $this->schema->translate_schema_string($this->schema->fields[$name]['title']) . "</div>\n";
                echo '<div class="value">';

                echo $field_value;

                echo "</div>\n";
                echo "</div>\n";
            }

            if (   !isset($config['end_fieldset'])
                || $fieldset_count <= 0) {
                // No more fieldsets to close
                continue;
            }

            if (is_numeric($config['end_fieldset'])) {
                for ($i = 0; $i < $config['end_fieldset']; $i++) {
                    echo "</div>\n";
                    $fieldset_count--;

                    if ($fieldset_count <= 0) {
                        break;
                    }
                }
            } else {
                echo "</div>\n";
                $fieldset_count--;
            }
        }
        echo "</div>\n";
    }
}
