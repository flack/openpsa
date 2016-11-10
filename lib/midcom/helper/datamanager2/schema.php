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
 * Schema definition, you need to use the appropriate subclass to handle them dynamically.
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
    var $fields = array();

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
     * @var midcom_services_i18n_l10n
     */
    public $l10n_schema = null;

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
    var $field_order = array();

    /**
     * The operations to add to the form.
     *
     * This is a simple array of commands, valid entries are 'save', 'cancel', 'next' and
     * 'previous', 'edit' is forbidden, other values are not interpreted by the DM infrastructure.
     *
     * @var Array
     */
    var $operations = array('save' => '', 'cancel' => '');

    /**
     * This array holds custom information attached to this schema. Its exact usage is component
     * dependant.
     *
     * @var Array
     */
    var $customdata = array();

    /**
     * Form-wide validation callbacks, executed by QuickForm. This is a list of arrays. Each
     * array defines a single callback, along with a snippet or file location that should be
     * auto-loaded in case the function is missing.
     *
     * @var Array
     */
    var $validation = array();

    /**
     * Custom data filter rules.
     *
     * This is a list of arrays. Each array defines a single callback,
     * a field list according to HTML_QuickForm::applyFilter() along with a snippet or file location
     * that should be auto-loaded in case the function is missing.
     *
     * @var Array
     */
    var $filters = array();

    /**
     * Construct a schema, takes a schema snippet URI resolvable through the
     * midcom_helper_misc::get_snippet_content() helper function.
     *
     * @param mixed $schemadb Either the path or the already loaded schema database
     *     to use.
     * @param string $name The name of the Schema to use. It must be a member in the
     *     specified schema database. If unspecified, the default schema is used.
     */
    public function __construct($schemadb, $name = null, $schemadb_path = null)
    {
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
     * already in memory, or from a URL resolvable by midcom_helper_misc::get_snippet_content.
     *
     * @param mixed $schemadb Either the path or the already loaded schema database
     *     to use.
     */
    private function _load_schemadb($schemadb)
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
                    midcom::get()->uimessages->add($this->_l10n->get('midcom.helper.datamanager2'), sprintf($this->_l10n->get('schema %s:%s extends itself'), $snippet_path, $schema_name), 'error');

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
                midcom::get()->uimessages->add($this->_l10n->get('midcom.helper.datamanager2'), sprintf($this->_l10n->get('extended schema %s:%s was not found'), $snippet_path, $schema_name), 'error');
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
     * Generate snippet link for the requested schemadb
     *
     * @param String $path
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
     * @see midcom_helper_misc::get_snippet_content()
     */
    private function _load_schemadb_contents($schemadb)
    {
        if (is_string($schemadb))
        {
            $data = midcom_helper_misc::get_snippet_content($schemadb);
            return midcom_helper_misc::parse_config($data);
        }
        else if (is_array($schemadb))
        {
            return $schemadb;
        }
        debug_print_r('Passed schema db was:', $schemadb);
        throw new midcom_error( 'Failed to access the schema database: Invalid variable type while constructing.');
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
    private function _load_schema($name)
    {
        // Setup the raw schema reference
        if (!array_key_exists($name, $this->_raw_schemadb))
        {
            throw new midcom_error("The schema {$name} was not found in the schema database.");
        }
        $this->_raw_schema =& $this->_raw_schemadb[$name];

        // Populate the l10n_schema member
        if (array_key_exists('l10n_db', $this->_raw_schema))
        {
            $l10n_name = $this->_raw_schema['l10n_db'];
        }
        else
        {
            $l10n_name = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_COMPONENT);
        }
        if (!midcom::get()->componentloader->is_installed($l10n_name))
        {
            $l10n_name = 'midcom';
        }
        $this->l10n_schema = $this->_i18n->get_l10n($l10n_name);

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

        if (   $this->_config->get('include_metadata_required')
            && $this->_schemadb_path
            && $this->_schemadb_path != midcom::get()->config->get('metadata_schema'))
        {
            // Include required fields from metadata schema to the schema
            $metadata_schema = midcom_helper_datamanager2_schema::load_database(midcom::get()->config->get('metadata_schema'));
            if (isset($metadata_schema['metadata']))
            {
                $prepended = false;
                foreach ($metadata_schema['metadata']->fields as $name => $field)
                {
                    if ($field['required'])
                    {
                        if (!$prepended)
                        {
                            $field['static_prepend'] = "<h3 style='clear: left;'>" . $this->_l10n_midcom->get('metadata') . "</h3>\n" . $field['static_prepend'];
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
     * @param array $config The fields' full configuration set.
     */
    function append_field($name, $config)
    {
        if (array_key_exists($name, $this->fields))
        {
            throw new midcom_error("Duplicate field {$name} encountered, schema operation is invalid. Aborting.");
        }

        $this->field_order[] = $name;
        $this->_complete_field_defaults($config);
        $this->fields[$name] = $config;
    }

    /**
     * This function removes a field from the schema
     *
     * @param string $name The name of the field to remove
     */
    public function remove_field($name)
    {
        if (!array_key_exists($name, $this->fields))
        {
            throw new midcom_error("Field {$name} not found.");
        }
        $this->field_order = array_diff($this->field_order, array($name));
        unset($this->fields[$name]);
    }

    /**
     * Internal helper function which completes all missing field declaration members
     * so that all fields can be treated uniformly.
     *
     * @todo Refactor in subfunctions for better readability.
     */
    private function _complete_field_defaults(array &$config)
    {
        // Sanity check for b0rken schemas, missing type/widget would cause DM & PHP to barf later on...
        if (empty($config['type']))
        {
            throw new midcom_error("Field '{$config['name']}' in schema '{$this->name}' loaded from {$this->_schemadb_path} is missing *type* definition");
        }
        if (empty($config['widget']))
        {
            throw new midcom_error("Field '{$config['name']}' in schema '{$this->name}' loaded from {$this->_schemadb_path} is missing *widget* definition");
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
            'index_method' => 'auto',
            'index_merge_with_content' => true,
            'customdata' => array()
        );
        $config = array_merge($simple_defaults, $config);

        // And complex ones
        if (!array_key_exists('storage', $config))
        {
            $config['storage'] = array
            (
                'location' => 'parameter',
                'domain' => 'midcom.helper.datamanager2'
            );
        }
        else
        {
            if (is_string($config['storage']))
            {
                $config['storage'] = array('location' => $config['storage']);
            }
            if (strtolower($config['storage']['location']) === 'parameter')
            {
                $config['storage']['location'] = strtolower($config['storage']['location']);
                if (!array_key_exists('domain', $config['storage']))
                {
                    $config['storage']['domain'] = 'midcom.helper.datamanager2';
                }
            }
        }

        if (   !array_key_exists('type_config', $config)
            || !is_array($config['type_config']))
        {
            $config['type_config'] = array();
        }
        if (   !array_key_exists('widget_config', $config)
            || !is_array($config['type_config']))
        {
            $config['widget_config'] = array();
        }

        $config['validation'] = $this->_complete_validation_field($config);
    }

    private function _complete_validation_field($config)
    {
        $validation = array();
        if (array_key_exists('validation', $config))
        {
            $validation = (array) $config['validation'];
        }

        foreach ($validation as $key => $rule)
        {
            if (!is_array($rule))
            {
                $rule = array('type' => $rule);
            }
            else if (!array_key_exists('type', $rule))
            {
                throw new midcom_error("Missing validation rule type for rule {$key} on field {$config['name']}, this is a required option.");
            }
            else if (   $rule['type'] == 'compare'
                     && !array_key_exists('compare_with', $rule))
            {
                throw new midcom_error("Missing compare_with option for compare type rule {$key} on field {$config['name']}, this is a required option.");
            }

            $defaults = array
            (
                'message' => "validation failed: {$rule['type']}",
                'format' => ''
            );

            $validation[$key] = array_merge($defaults, $rule);
        }
        return $validation;
    }

    /**
     * Helper function which transforms a raw schema database (either already parsed or
     * based on a URL to a schemadb) into a list of schema class instances.
     *
     * @param mixed $raw_db Either an already created raw schema array, or a midcom_helper_misc::get_snippet_content
     *     compatible URL to a snippet / file from which the db should be loaded or schemadb contents as a string.
     * @return midcom_helper_datamanager2_schema[]
     * @see midcom_helper_misc::get_snippet_content()
     */
    public static function load_database($raw_db)
    {
        static $loaded_dbs = array();
        $path = null;
        if (is_string($raw_db))
        {
            // Determine if the given string is a path - assume that a path
            // doesn't have line breaks
            if (preg_match('/\n/', $raw_db))
            {
                $raw_db = midcom_helper_misc::parse_config($raw_db);
            }
            else
            {
                $path = $raw_db;
                if (!array_key_exists($path, $loaded_dbs))
                {
                    $data = midcom_helper_misc::get_snippet_content($raw_db);
                    $loaded_dbs[$path] = midcom_helper_misc::parse_config($data);
                }

                $raw_db = $loaded_dbs[$path];
            }
        }

        if (!is_array($raw_db))
        {
            throw new midcom_error("Provided DM2 schema is not in Array format.");
        }

        $schemadb = array();

        foreach (array_keys($raw_db) as $name)
        {
            $schemadb[$name] = new static($raw_db, $name, $path);
        }

        return $schemadb;
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
    public function translate_schema_string($string)
    {
        $translate_string = strtolower($string);

        if ($this->l10n_schema->string_available($translate_string))
        {
            return $this->l10n_schema->get($translate_string);
        }
        if ($this->_l10n->string_available($translate_string))
        {
            return $this->_l10n->get($translate_string);
        }
        if ($this->_l10n_midcom->string_available($translate_string))
        {
            return $this->_l10n_midcom->get($translate_string);
        }

        return $string;
    }
}
