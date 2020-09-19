<?php
/**
 * @package midgard.admin.asgard
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\schemadb;

/**
 * Helper class to create a DM schema from an object via reflection
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_schemadb
{
    /**
     * The object we're working with
     *
     * @var midcom_core_dbaobject
     */
    private $_object;

    /**
     * Component config for Asgard
     *
     * @var midcom_helper_configuration
     */
    private $_config;

    /**
     * The schema in use
     *
     * @var array
     */
    private $schema;

    /**
     * Midgard reflection property instance for the current object's class.
     *
     * @var midgard_reflection_property
     */
    private $_reflector;

    /**
     * @var midcom_services_i18n_l10n
     */
    private $l10n;

    /**
     * Flag that controls if fields used for copying should be added
     *
     * @var boolean
     */
    public $add_copy_fields = false;

    public function __construct($object, midcom_helper_configuration $config, ?string $type = null)
    {
        if ($type != null) {
            $this->_object = new $type();
        } else {
            $this->_object = $object;
        }

        if (!midcom::get()->dbclassloader->is_midcom_db_object($this->_object)) {
            $this->_object = midcom::get()->dbfactory->convert_midgard_to_midcom($this->_object);
        }
        $this->_reflector = new midgard_reflection_property(midcom_helper_reflector::resolve_baseclass($this->_object));
        $this->_config = $config;
        $this->l10n = midcom::get()->i18n->get_l10n('midgard.admin.asgard');
    }

    /**
     * Generates, loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    public function create($include_fields) : schemadb
    {
        $type = get_class($this->_object);
        $type_fields = $this->_object->get_properties();

        $this->schema = [
            'description' => 'object schema',
            'l10n_db'     => 'midgard.admin.asgard',
            'fields'      => []
        ];

        if ($component = midcom::get()->dbclassloader->get_component_for_class($type)) {
            $this->schema['l10n_db'] = $component;
        }

        if (!empty($include_fields)) {
            // Skip the fields that aren't requested, if inclusion list has been defined
            $type_fields = array_intersect($type_fields, (array) $include_fields);
        }

        $type_fields = array_filter($type_fields, [$this, '_filter_schema_fields']);

        usort($type_fields, [$this, 'sort_schema_fields']);

        // Iterate through object properties
        foreach ($type_fields as $key) {
            // Linked fields should use chooser
            if ($this->_reflector->is_link($key)) {
                $this->_add_linked_field($key);
                // Skip rest of processing
                continue;
            }

            $field_type = $this->_reflector->get_midgard_type($key);
            switch ($field_type) {
                case MGD_TYPE_GUID:
                case MGD_TYPE_STRING:
                    $this->_add_string_field($key, $type);
                    break;
                case MGD_TYPE_LONGTEXT:
                    $this->_add_longtext_field($key);
                    break;
                case MGD_TYPE_INT:
                case MGD_TYPE_UINT:
                    $this->_add_int_field($key);
                    break;
                case MGD_TYPE_FLOAT:
                    $this->schema['fields'][$key] = [
                        'title'       => $key,
                        'storage'     => $key,
                        'type'        => 'number',
                        'widget'      => 'text',
                    ];
                    break;
                case MGD_TYPE_BOOLEAN:
                    $this->schema['fields'][$key] = [
                        'title'       => $key,
                        'storage'     => $key,
                        'type'        => 'boolean',
                        'widget'      => 'checkbox',
                    ];
                    break;
                case MGD_TYPE_TIMESTAMP:
                    $this->schema['fields'][$key] = [
                        'title'       => $key,
                        'storage'     => $key,
                        'type' => 'date',
                        'widget' => 'jsdate',
                    ];
                    break;
            }
        }

        $this->_add_rcs_field();

        if ($this->add_copy_fields) {
            $this->_add_copy_fields();
        }

        return new schemadb(['default' => $this->schema]);
    }

    private function _filter_schema_fields(string $key) : bool
    {
        if (   $key == 'metadata'
            || in_array($key, $this->_config->get('object_skip_fields'))) {
            return false;
        }

        return true;
    }

    private function _add_string_field(string $key, string $type)
    {
        if (   $key == 'component'
            && $type == midcom_db_topic::class) {
            $this->_add_component_dropdown($key);
            return;
        }

        // Special name handling, start by checking if given type is same as $this->_object and if not making a dummy copy (we're probably in creation mode then)
        if ($this->_object instanceof $type) {
            $name_obj = $this->_object;
        } else {
            $name_obj = new $type();
        }

        if ($key === midcom_helper_reflector::get_name_property($name_obj)) {
            $this->_add_name_field($key, $name_obj);
            return;
        }

        $this->schema['fields'][$key] = [
            'title'       => $key,
            'storage'     => $key,
            'type'        => 'text',
            'widget'      => 'text',
        ];
    }

    private function _add_rcs_field()
    {
        $this->schema['fields']['_rcs_message'] = [
            'title'       => $this->l10n->get('revision comment'),
            'storage'     => null,
            'type'        => 'rcsmessage',
            'widget'      => 'text',
            'start_fieldset' => [
                'title' => $this->l10n->get('revision'),
                'css_group' => 'rcs',
            ],
            'end_fieldset' => '',
        ];
    }

    private function _add_int_field(string $key)
    {
        if (in_array($key, ['start', 'end', 'added', 'date'])) {
            // We can safely assume that INT fields called start and end store unixtimes
            $this->schema['fields'][$key] = [
                'title'       => $key,
                'storage'     => $key,
                'type' => 'date',
                'type_config' => [
                    'storage_type' => 'UNIXTIME'
                    ],
                'widget' => 'jsdate',
            ];
        } else {
            $this->schema['fields'][$key] = [
                'title'       => $key,
                'storage'     => $key,
                'type'        => 'number',
                'widget'      => 'text',
            ];
        }
    }

    private function _add_longtext_field(string $key)
    {
        // Figure out nice size for the editing field

        $output_mode = '';
        $widget = 'textarea';
        $dm_type = 'text';

        switch ($key) {
            case 'content':
            case 'description':
                $height = 30;

                // Check the user preference and configuration
                if (   midgard_admin_asgard_plugin::get_preference('tinymce_enabled')
                    || (   midgard_admin_asgard_plugin::get_preference('tinymce_enabled') !== '0'
                        && $this->_config->get('tinymce_enabled'))) {
                    $widget = 'tinymce';
                }
                $output_mode = 'html';

                break;
            case 'value':
            case 'code':
                // These are typical "large" fields
                $height = 30;

                // Check the user preference and configuration
                if (   midgard_admin_asgard_plugin::get_preference('codemirror_enabled')
                    || (   midgard_admin_asgard_plugin::get_preference('codemirror_enabled') !== '0'
                        && $this->_config->get('codemirror_enabled'))) {
                    $widget = 'codemirror';
                }

                $dm_type = 'php';
                $output_mode = 'code';

                break;

            default:
                $height = 6;
                break;
        }

        $this->schema['fields'][$key] = [
            'title'       => $key,
            'storage'     => $key,
            'type'        => $dm_type,
            'type_config' => [
                'output_mode' => $output_mode,
            ],
            'widget'      => $widget,
            'widget_config' => [
                'height' => $height,
                'width' => '100%',
            ],
        ];
    }

    private function _add_name_field(string $key, midcom_core_dbaobject $name_obj)
    {
        $type_urlname_config = [];
        $allow_unclean_name_types = $this->_config->get('allow_unclean_names_for');
        foreach ($allow_unclean_name_types as $allow_unclean_name_types_type) {
            if ($name_obj->__object instanceof $allow_unclean_name_types_type) {
                $type_urlname_config['allow_unclean'] = true;
                break;
            }
        }

        // Enable generating the name from the title property
        $type_urlname_config['title_field'] = midcom_helper_reflector::get_title_property($name_obj);

        $this->schema['fields'][$key] = [
            'title'       => $key,
            'storage'     => $key,
            'type'        => 'urlname',
            'type_config' => $type_urlname_config,
            'widget'      => 'text',
        ];
    }

    private function _add_component_dropdown(string $key)
    {
        $components = ['' => ''];
        foreach (midcom::get()->componentloader->get_manifests() as $manifest) {
            // Skip purecode components
            if ($manifest->purecode) {
                continue;
            }

            $components[$manifest->name] = midcom::get()->i18n->get_string($manifest->name, $manifest->name) . " ({$manifest->name})";
        }
        asort($components);

        $this->schema['fields'][$key] = [
            'title'       => $key,
            'storage'     => $key,
            'type'        => 'select',
            'type_config' => [
                'options' => $components,
            ],
            'widget'      => 'select',
        ];
    }

    private function _add_linked_field(string $key)
    {
        $linked_type = $this->_reflector->get_link_name($key);
        $field_type = $this->_reflector->get_midgard_type($key);

        if ($key == 'up') {
            $field_label = sprintf($this->l10n->get('under %s'), midgard_admin_asgard_plugin::get_type_label($linked_type));
        } else {
            $type_label = midgard_admin_asgard_plugin::get_type_label($linked_type);
            if (str_starts_with($type_label, $key)) {
                // Handle abbreviations like "lang" for "language"
                $field_label = $type_label;
            } elseif ($key == $type_label) {
                $field_label = $key;
            } else {
                $ref = midcom_helper_reflector::get($this->_object);
                $component_l10n = $ref->get_component_l10n();
                $field_label = sprintf($this->l10n->get('%s (%s)'), $component_l10n->get($key), $type_label);
            }
        }

        // Get the chooser widgets
        switch ($field_type) {
            case MGD_TYPE_UINT:
            case MGD_TYPE_STRING:
            case MGD_TYPE_GUID:
                $class = midcom::get()->dbclassloader->get_midcom_class_name_for_mgdschema_object($linked_type);
                if (!$class) {
                    break;
                }
                $this->schema['fields'][$key] = [
                    'title'       => $field_label,
                    'storage'     => $key,
                    'type'        => 'select',
                    'type_config' => [
                        'require_corresponding_option' => false,
                        'options' => [],
                        'allow_other' => true,
                        'allow_multiple' => false,
                    ],
                    'widget' => 'autocomplete',
                    'widget_config' => $this->build_autocomplete_config($key, $class, $linked_type),
                    'required' => (midgard_object_class::get_property_parent($this->_object->__mgdschema_class_name__) == $key)
                ];
                break;
        }
    }

    private function build_autocomplete_config(string $key, string $class, string $linked_type) : array
    {
        $reflector = midcom_helper_reflector::get($linked_type);
        $component = midcom::get()->dbclassloader->get_component_for_class($linked_type);
        $searchfields = $reflector->get_search_properties();
        $label_property = $reflector->get_label_property();
        $has_parent = !empty(midgard_object_class::get_property_parent($linked_type)) || !empty(midgard_object_class::get_property_up($linked_type));
        $result_headers = [];

        foreach ($searchfields as $field) {
            if ($field !== $label_property) {
                $result_headers[] = [
                    'name' => $field,
                    'title' => ucfirst($field),
                ];
            }
        }
        $searchfields[] = 'guid';

        return [
            'class' => $class,
            'component' => $component,
            'titlefield' => method_exists($class, 'get_label') ? null : $label_property,
            'id_field' => $this->_reflector->get_link_target($key),
            'searchfields' => $searchfields,
            'result_headers' => $result_headers,
            'orders' => [],
            'creation_mode_enabled' => true,
            'creation_handler' => midcom_connection::get_url('self') . "__mfa/asgard/object/create/chooser/{$linked_type}/",
            'creation_default_key' => $reflector->get_title_property(new $linked_type),
            'categorize_by_parent_label' => $has_parent
        ];
    }

    private function _add_copy_fields()
    {
        // Add switch for copying parameters
        $this->schema['fields']['parameters'] = [
            'title'       => $this->l10n->get('copy parameters'),
            'storage'     => null,
            'type'        => 'boolean',
            'widget'      => 'checkbox',
            'default'     => true,
        ];

        // Add switch for copying metadata
        $this->schema['fields']['metadata'] = [
            'title'       => $this->l10n->get('copy metadata'),
            'storage'     => null,
            'type'        => 'boolean',
            'widget'      => 'checkbox',
            'default'     => true,
        ];

        // Add switch for copying attachments
        $this->schema['fields']['attachments'] = [
            'title'       => $this->l10n->get('copy attachments'),
            'storage'     => null,
            'type'        => 'boolean',
            'widget'      => 'checkbox',
            'default'     => true,
        ];

        // Add switch for copying privileges
        $this->schema['fields']['privileges'] = [
            'title'       => $this->l10n->get('copy privileges'),
            'storage'     => null,
            'type'        => 'boolean',
            'widget'      => 'checkbox',
            'default'     => true,
        ];
    }

    private function _get_score(string $field) : int
    {
        $preferred_fields = $this->_config->get('object_preferred_fields');
        $timerange_fields = $this->_config->get('object_timerange_fields');
        $phone_fields = $this->_config->get('object_phone_fields');
        $address_fields = $this->_config->get('object_address_fields');
        $location_fields = $this->_config->get('object_location_fields');

        $score = 7;

        if ($this->_reflector->get_midgard_type($field) == MGD_TYPE_LONGTEXT) {
            $score = 1;
        } elseif (in_array($field, $preferred_fields)) {
            $score = 0;
        } elseif ($this->_reflector->is_link($field)) {
            $score = 2;
        } elseif (in_array($field, $timerange_fields)) {
            $score = 3;
        } elseif (in_array($field, $phone_fields)) {
            $score = 4;
        } elseif (in_array($field, $address_fields)) {
            $score = 5;
        } elseif (in_array($field, $location_fields)) {
            $score = 6;
        }

        return $score;
    }

    public function sort_schema_fields($first, $second)
    {
        $score1 = $this->_get_score($first);
        $score2 = $this->_get_score($second);
        if ($score1 < $score2) {
            return -1;
        }
        if ($score1 > $score2) {
            return 1;
        }
        if (   $score1 < 3
            || $score1 > 6) {
            return strnatcmp($first, $second);
        }
        switch ($score1) {
            case 3:
                $type = 'timerange';
                break;
            case 4:
                $type = 'phone';
                break;
            case 5:
                $type = 'address';
                break;
            case 6:
                $type = 'location';
                break;
        }
        $fields = $this->_config->get('object_' . $type . '_fields');
        return array_search($first, $fields) <=> array_search($second, $fields);
    }
}
