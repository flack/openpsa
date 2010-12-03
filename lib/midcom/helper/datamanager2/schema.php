<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 Schema class.
 *
 * This class encapsulates Datamanager Schemas. It contains all information required to construct
 * the types and widgets of a given data schema. The base class constructs out of a Datamanager 2
 * Schema definition, you need to
 * use the appropriate subclass to handle them dynamically.
 *
 * <b>Schema Definition</b>
 *
 * <b>Storage</b>
 * When using the Midgard storage backend, it is possible to define a callback class to be called
 * that will then save the object. The class is defined as follows. Please note that if the classname
 * follows the midcom hierarchy, it may be loaded automatically.
 *
 * The class must satisfy the following interfaces:
 * <code>
 * class midcom_admin_parameters_callback {
 *      // params:
 *      // name: the name of the field
 *      // data: the data that comes from the type defined.
 *      // storage: a reference to the datamanager's storageclass.
 *      function on_load_data($name,&$storage);
 *      function on_store_data($name, $data,&$storage);
 * }
 * </code>
 *
 * What the functions should return depends on the datatype they return to.
 *
 * The callback may be defined in the schema like this:
 * <code>
 * 'fields' => Array
 * (
 *      'parameters' => Array
 *       (
 *           'title' => 'url name',
 *           'storage' => Array
 *            (
 *                   'location' => 'object',
 *                   'callback' => 'midcom_admin_parameters_callbacks_storage',
 *            ),
 *            'type' => ..,
 *            'widget' => ..
 *       ),
 * </code>
 *
 * <b>Important</b>
 * It is only possible to define one storage callback per schema! If you want more than one,
 * encapsulate this in your class.
 *
 * @todo Complete documentation
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_schema extends midcom_baseclasses_components_purecode
{
    /**
     * The general field listing, indexed by their name. It contains the full field record
     * which has been completed with all defaults.
     *
     * You may changes settings outlined here, be aware though, that types and/or widgets
     * spawned based on this schema do normally not reference these configuration block
     * directly, but instead they usually merge the settings made into their own internal
     * default config. You can still adjust the types, which usually have their settings
     * available as public members though.
     *
     * @var Array
     */
    var $fields = Array();

    /**
     * The title of this schema, used to display schemas when
     *
     * @var string
     */
    var $description = '';

    /**
     * The name of the schema ("identifier").
     *
     * @var string
     */
    var $name = '';

    /**
     * The primary L10n DB to use for schema translation.
     *
     * @var midcom_services__i18n_l10n
     */
    var $l10n_schema = null;

    /**
     * The raw schema array as read by the system. This is a reference
     * into the schema database.
     *
     * @var Array
     */
    private $_raw_schema = null;

    /**
     * The raw schema database as read by the system.
     *
     * @var Array
     */
    private $_raw_schemadb = null;

    /**
     * The schema database path as read by the system.
     *
     * @var Array
     */
    private $_schemadb_path = null;

    /**
     * A simple array holding the fields in the order they should be rendered identified
     * by their name.
     *
     * @var Array
     */
    var $field_order = Array();

    /**
     * The operations to add to the form.
     *
     * This is a simple array of commands, valid entries are 'save', 'cancel', 'next' and
     * 'previous', 'edit' is forbidden, other values are not interpreted by the DM infrastructure.
     *
     * @var Array
     */
    var $operations = Array('save' => '', 'cancel' => '');

    /**
     * This array holds custom information attached to this schema. Its exact usage is component
     * dependant.
     *
     * @var Array
     */
    var $customdata = Array();

    /**
     * Form-wide validation callbacks, executed by QuickForm. This is a list of arrays. Each
     * array defines a single callback, along with a snippet or file location that should be
     * auto-loaded in case the function is missing.
     *
     * @var Array
     */
    var $validation = Array();

    /**
     * Custom data filter rules.
     *
     * This is a list of arrays. Each array defines a single callback,
     * a field list according to HTML_QuickForm::applyFilter() along with a snippet or file location
     * that should be auto-loaded in case the function is missing.
     *
     * @var Array
     */
    var $filters = Array();

    /**
     * Construct a schema, takes a schema snippet URI resolvable through the
     * midcom_get_snippet_content() helper function.
     *
     * @param mixed $schemadb Either the path or the already loaded schema database
     *     to use.
     * @param string $name The name of the Schema to use. It must be a member in the
     *     specified schema database. If unspecified, the default schema is used.
     * @see midcom_get_snippet_content()
     */
    public function __construct($schemadb, $name = null, $schemadb_path = null)
    {
        $this->_component = 'midcom.helper.datamanager2';
        parent::__construct();
        $this->_schemadb_path = $schemadb_path;

        $this->_load_schemadb($schemadb);

        if ($name === null)
        {
            reset($this->_raw_schemadb);
            $name = key($this->_raw_schemadb);
        }

        $this->_load_schema($name);
    }

    /**
     * This function loads the schema database into the class, either from a copy
     * already in memory, or from a URL resolvable by midcom_get_snippet_content.
     *
     * @param mixed $schemadb Either the path or the already loaded schema database
     *     to use.
     * @see midcom_get_snippet_content()
     */
    function _load_schemadb($schemadb)
    {
        $contents = $this->_load_schemadb_contents($schemadb);

        foreach ($contents as $schema_name => $schema)
        {
            if (!isset($schema['extends']))
            {
                continue;
            }

            // Default extended schema is with the same name
            $extended_schema_name = $schema_name;
            $path = $schemadb;

            if (is_array($schema['extends']))
            {
                if (isset($schema['extends']['path']))
                {
                    $path = $schema['extends']['path'];
                }

                // Override schema name
                if (isset($schema['extends']['name']))
                {
                    $extended_schema_name = $schema['extends']['name'];
                }
            }
            elseif (isset($contents[$schema['extends']]))
            {
                $schema['extends'] = array
                (
                    'name' => $schema['extends'],
                );
            }
            else
            {
                $path = $schema['extends'];
            }

            if ($path === $schemadb)
            {
                // Infinite loop, set an UI message and stop executing
                if (   !isset($schema['extends']['name'])
                    || $schema['extends']['name'] === $schema_name)
                {
                    $snippet_path = $this->_get_snippet_link($path);
                    $_MIDCOM->uimessages->add($this->_l10n->get('midcom.helper.datamanager2'), sprintf($this->_l10n->get('schema %s:%s extends itself'), $snippet_path, $schema_name), 'error');

                    debug_add(sprintf($this->_l10n->get('schema %s:%s extends itself'), $path, $schema_name), MIDCOM_LOG_WARN);
                    continue;
                }

                $extended_schemadb[$schema['extends']['name']] = $contents[$schema['extends']['name']];
                $extended_schema_name = $schema['extends']['name'];
            }
            else
            {
                $extended_schemadb = $this->_load_schemadb($path);
            }

            // Raise a notice if extended schema was not found from the schemadb
            if (!isset($extended_schemadb[$extended_schema_name]))
            {
                debug_add(sprintf($this->_l10n->get('extended schema %s:%s was not found'), $path, $schema_name), MIDCOM_LOG_WARN);

                $snippet_path = $this->_get_snippet_link($path);
                $_MIDCOM->uimessages->add($this->_l10n->get('midcom.helper.datamanager2'), sprintf($this->_l10n->get('extended schema %s:%s was not found'), $snippet_path, $schema_name), 'error');
                continue;
            }

            // Override the extended schema with fields from the new schema
            foreach ($contents[$schema_name] as $key => $value)
            {
                if ($key === 'extends')
                {
                    continue;
                }

                // This is probably either fields or operations
                if (is_array($value))
                {
                    if (!isset($extended_schemadb[$extended_schema_name][$key]))
                    {
                        $extended_schemadb[$extended_schema_name][$key] = array();
                    }

                    foreach ($value as $name => $field)
                    {
                        if (!$field)
                        {
                            unset($extended_schemadb[$extended_schema_name][$key][$name]);
                            continue;
                        }

                        $extended_schemadb[$extended_schema_name][$key][$name] = $field;
                    }
                }
                else
                {
                    $extended_schemadb[$extended_schema_name][$key] = $value;
                }
            }

            // Replace the new schema with extended schema
            $contents[$schema_name] = $extended_schemadb[$extended_schema_name];
        }

        $this->_raw_schemadb = $contents;
        return $contents;
    }

    /**
     * Get snippet link. A small helper for generating link for the requested schemadb
     *
     * @param String $schemadb
     * @return String Link tag to the loaded object
     */
    private function _get_snippet_link($path)
    {
        if (!is_string($path))
        {
            return false;
        }

        $snippet = new midgard_snippet();
        try
        {
            $snippet->get_by_path($path);

            if ($snippet->guid)
            {
                return "<a href=\"" . midcom_connection::get_url('self') . "__mfa/asgard/object/edit/{$snippet->guid}/\">{$path}</a>";
            }
        }
        catch (Exception $e)
        {
        }

        return $path;
    }

    /**
     * Load the schemadb contents
     *
     * @param mixed $schemadb    Path of the schemadb or raw schema array
     * @return array             Containing schemadb definitions
     */
    private function _load_schemadb_contents($schemadb)
    {
        if (is_string($schemadb))
        {
            $data = midcom_get_snippet_content($schemadb);
            $result = eval ("\$contents = array ( {$data}\n );");
            if ($result === false)
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                    "Failed to parse the schema definition in '{$schemadb}', see above for PHP errors.");
                // This will exit.
            }

            return $contents;
        }
        else if (is_array($schemadb))
        {
            return $schemadb;
        }
        else
        {
            debug_print_r('Passed schema db was:', $schemadb);
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Failed to access the schema database: Invalid variable type while constructing.');
            // This will exit.
        }

        return false;
    }

    /**
     * This function parses the schema and populates all members with the corresponding
     * information, completing defaults where necessary.
     *
     * It will automatically translate all descriptive fields according to the rules
     * outlined in the translate_schema_field() helper function.
     *
     * @param string $name The name of the schema to load.
     */
    function _load_schema($name)
    {
        // Setup the raw schema reference
        if (! array_key_exists($name, $this->_raw_schemadb))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "The schema {$name} was not found in the schema database.");
            // This will exit.
        }
        $this->_raw_schema =& $this->_raw_schemadb[$name];

        // Populate the l10n_schema member
        if (array_key_exists('l10n_db', $this->_raw_schema))
        {
            $l10n_name = $this->_raw_schema['l10n_db'];
        }
        else
        {
            $l10n_name = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_COMPONENT);
        }
        $this->_l10n_schema = $_MIDCOM->i18n->get_l10n($l10n_name);

        if (array_key_exists('operations', $this->_raw_schema))
        {
            $this->operations = $this->_raw_schema['operations'];
        }
        if (array_key_exists('customdata', $this->_raw_schema))
        {
            $this->customdata = $this->_raw_schema['customdata'];
        }
        if (array_key_exists('validation', $this->_raw_schema))
        {
            $this->validation = $this->_raw_schema['validation'];
        }
        if (array_key_exists('filters', $this->_raw_schema))
        {
            $this->filters = $this->_raw_schema['filters'];
        }

        $this->description = $this->_raw_schema['description'];
        $this->name = $name;

        foreach ($this->_raw_schema['fields'] as $name => $data)
        {
            $data['name'] = $name;
            $this->append_field($name, $data);
        }

        if (   $this->_config
            && $this->_config->get('include_metadata_required')
            && $this->_schemadb_path
            && $this->_schemadb_path != $GLOBALS['midcom_config']['metadata_schema'])
        {
            // Include required fields from metadata schema to the schema
            $metadata_schema = midcom_helper_datamanager2_schema::load_database($GLOBALS['midcom_config']['metadata_schema']);
            if (isset($metadata_schema['metadata']))
            {
                $prepended = false;
                foreach ($metadata_schema['metadata']->fields as $name => $field)
                {
                    if ($field['required'])
                    {
                        if (!$prepended)
                        {
                            $field['static_prepend'] = "<h3 style='clear: left;'>" . $_MIDCOM->i18n->get_string('metadata', 'midcom') . "</h3>\n" . $field['static_prepend'];
                            $prepended = true;
                        }
                        $this->append_field($name, $field);
                    }
                }
            }
        }
    }

    /**
     * This function adds a new field to the schema, appending it at the end of the
     * current field listing.
     *
     * This is callable after the construction of the object, to allow you to add
     * additional fields like component required fields to the list.
     *
     * This can also be used to merge schemas together.
     *
     * It will complete the field's default and set the corresponding type and widget
     * setups.
     *
     * @param string $name The name of the field to add
     * @param Array $config The fields' full configuration set.
     */
    function append_field($name, $config)
    {
        if (array_key_exists($name, $this->fields))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Duplicate field {$name} encountered, schema operation is invalid. Aborting.");
            // This will exit.
        }

        $this->field_order[] = $name;
        $this->_complete_field_defaults($config);
        $this->fields[$name] = $config;
    }

    /**
     * Internal helper function which completes all missing field declaration members
     * so that all fields can be treated uniformly.
     *
     * @todo Refactor in subfunctions for better readability.
     */
    function _complete_field_defaults(&$config)
    {
        // Sanity check for b0rken schemas, missing type/widget would cause DM & PHP to barf later on...
        if (   !array_key_exists('type', $config)
            || empty($config['type']))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Field '{$config['name']}' in schema '{$this->name}' loaded from {$this->_schemadb_path} is missing *type* definition");
            // this will exit
        }
        if (   !array_key_exists('widget', $config)
            || empty($config['widget']))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Field '{$config['name']}' in schema '{$this->name}' loaded from {$this->_schemadb_path} is missing *widget* definition");
            // this will exit
        }
        /* Rest of the defaults */
        // Simple ones
        $simple_defaults = array
        (
            'description' => null,
            'helptext' => null,
            'static_prepend' => null,
            'static_append' => null,
            'read_privilege' => null,
            'write_privilege' => null,
            'default' => null,
            'readonly' => false,
            'hidden' => false,
            'required' => false,
        );
        foreach ($simple_defaults as $property => $value)
        {
            if (! array_key_exists( $property, $config))
            {
                $config[ $property] = $value;
            }
        }
        unset($property, $value);

        // And complex ones
        if (! array_key_exists('storage', $config))
        {
            $config['storage'] = Array
            (
                'location' => 'parameter',
                'domain' => 'midcom.helper.datamanager2'
            );
        }
        else
        {
            if (is_string($config['storage']))
            {
                $config['storage'] = Array ( 'location' => $config['storage'] );
            }
            if (strtolower($config['storage']['location']) === 'parameter')
            {
                $config['storage']['location'] = strtolower($config['storage']['location']);
                if (! array_key_exists('domain', $config['storage']))
                {
                    $config['storage']['domain'] = 'midcom.helper.datamanager2';
                }
            }
        }
        if (! array_key_exists('index_method', $config))
        {
            $config['index_method'] = 'auto';
        }
        if (! array_key_exists('index_merge_with_content', $config))
        {
            $config['index_merge_with_content'] = true;
        }

        if (   ! array_key_exists('type_config', $config)
            || ! is_array($config['type_config']))
        {
            $config['type_config'] = Array();
        }
        if (   ! array_key_exists('widget_config', $config)
            || ! is_array($config['type_config']))
        {
            $config['widget_config'] = Array();
        }
        if (! array_key_exists('customdata', $config))
        {
            $config['customdata'] = Array();
        }

        if (   ! array_key_exists('validation', $config)
            || ! $config['validation'])
        {
            $config['validation'] = Array();
        }
        else if (! is_array($config['validation']))
        {
            $config['validation'] = Array($config['validation']);
        }
        foreach ($config['validation'] as $key => $rule)
        {
            if (! is_array($rule))
            {
                if ($rule['type'] == 'compare')
                {
                    $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                        "Missing compare_with option for compare type rule {$key} on field {$config['name']}, this is a required option.");
                    // This will exit.
                }
                $config['validation'][$key] = Array
                (
                    'type' => $rule,
                    'message' => "validation failed: {$rule}",
                    'format' => ''
                );
            }
            else
            {
                if (! array_key_exists('type', $rule))
                {
                    $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                        "Missing validation rule type for rule {$key} on field {$config['name']}, this is a required option.");
                    // This will exit.
                }
                if (! array_key_exists('message', $rule))
                {
                    $config['validation'][$key]['message'] = "validation failed: {$rule['type']}";
                }
                if (! array_key_exists('format', $rule))
                {
                    $config['validation'][$key]['format'] = '';
                }
                if ($rule['type'] == 'compare')
                {
                    if (! array_key_exists('compare_with', $rule))
                    {
                        $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                            "Missing compare_with option for compare type rule {$key} on field {$config['name']}, this is a required option.");
                        // This will exit.
                    }
                }
            }
        }
    }

    /**
     * Schema translation helper, usable by components from the outside.
     *
     * The l10n db from the schema is used first, the Datamanager l10n db second and
     * the MidCOM core l10n db last. If the string is not found in both databases,
     * the string is returned unchanged.
     *
     * Note, that the string is translated to <i>lower case</i> before
     * translation, as this is the usual form how strings are in the
     * l10n database. (This is for backwards compatibility mainly.)
     *
     * @param string $string The string to be translated.
     * @return string The translated string.
     */
    function translate_schema_string ($string)
    {
        $translate_string = strtolower($string);

        if (   $this->_l10n_schema !== null
            && $this->_l10n_schema->string_available($translate_string))
        {
            return $this->_l10n_schema->get($translate_string);
        }
        else if ($this->_l10n->string_available($translate_string))
        {
            return $this->_l10n->get($translate_string);
        }
        else if ($this->_l10n_midcom->string_available($translate_string))
        {
            return $this->_l10n_midcom->get($translate_string);
        }

        return $string;
    }

    /**
     * Helper function which transforms a raw schema database (either already parsed or
     * based on a URL to a schemadb) into a list of schema class instances.
     *
     * This function may (and usually will) be called statically.
     *
     * @param mixed $raw_db Either an already created raw schema array, or a midgard_get_snippet_content
     *     compatible URL to a snippet / file from which the db should be loaded or schemadb contents as a string.
     * @return Array An array of midcom_helper_datamanager2_schema class instances.
     * @see midcom_get_snippet_content()
     */
    function load_database($raw_db)
    {
        $path = null;
        if (is_string($raw_db))
        {
            // Determine if the given string is a path - assume that a path
            // doesn't have line breaks
            if (preg_match('/\n/', $raw_db))
            {
                $result = eval("\$raw_db = array ( {$raw_db} );");
            }
            else
            {
                $path = $raw_db;
                $data = midcom_get_snippet_content($raw_db);
                $result = eval ("\$raw_db = array ( {$data}\n );");
            }

            // Bullet-proof against syntax errors
            if ($result === false)
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                    "Failed to parse the schema database loaded from '{$raw_db}', see above for PHP errors.");
                // This will exit.
            }
        }

        $schemadb = array();

        if (!is_array($raw_db))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Provided DM2 schema is not in Array format.");
        }

        foreach ($raw_db as $name => $raw_schema)
        {
            $schemadb[$name] = new midcom_helper_datamanager2_schema($raw_db, $name, $path);
        }
        return $schemadb;
    }

    /**
     * Registers a schema into the session so it is readable by the imagepopup.
     *
     * @return string the form sessionkey
     */
    public function register_to_session($guid)
    {
        $key = $this->name .  $guid;
        // Seems we do not need this anymore, but return key still
        return $key;

        $session = $_MIDCOM->get_service('session');
        $session->set('midcom.helper.datamanager2', $key, $this->_raw_schema);
        return $key;
    }
}
?>