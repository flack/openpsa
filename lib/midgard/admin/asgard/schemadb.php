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
     * Flag that controls if fields used for copying should be added
     *
     * @var boolean
     */
    public $add_copy_fields = false;

    public function __construct($object, $config, $type = null)
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
        $this->_l10n = midcom::get()->i18n->get_l10n('midgard.admin.asgard');
    }

    /**
     * Generates, loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    public function create($include_fields)
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
                    $this->_add_longtext_field($key, $type);
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

        return new schemadb(['object' => $this->schema]);
    }

    private function _filter_schema_fields($key)
    {
        if (   $key == 'metadata'
            || in_array($key, $this->_config->get('object_skip_fields'))) {
            return false;
        }

        return true;
    }

    private function _add_string_field($key, $type)
    {
        if (   $key == 'component'
            && $type == 'midcom_db_topic') {
            $this->_add_component_dropdown($key);
            return;
        }

        // Special name handling, start by checking if given type is same as $this->_object and if not making a dummy copy (we're probably in creation mode then)
        if (midcom::get()->dbfactory->is_a($this->_object, $type)) {
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
            'title'       => $this->_l10n->get('revision comment'),
            'storage'     => null,
            'type'        => 'rcsmessage',
            'widget'      => 'text',
            'start_fieldset' => [
                'title' => $this->_l10n->get('revision'),
                'css_group' => 'rcs',
            ],
            'end_fieldset' => '',
        ];
    }

    private function _add_int_field($key)
    {
        if (   $key == 'start'
            || $key == 'end'
            || $key == 'added'
            || $key == 'date') {
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

    private function _add_longtext_field($key, $type)
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

    private function _add_name_field($key, $name_obj)
    {
        $type_urlname_config = [];
        $allow_unclean_name_types = $this->_config->get('allow_unclean_names_for');
        foreach ($allow_unclean_name_types as $allow_unclean_name_types_type) {
            if (midcom::get()->dbfactory->is_a($name_obj, $allow_unclean_name_types_type)) {
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

    private function _add_component_dropdown($key)
    {
        $components = ['' => ''];
        foreach (midcom::get()->componentloader->manifests as $manifest) {
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
            'widget'      => 'midcom_admin_folder_selectcomponent',
        ];
    }

    private function _add_linked_field($key)
    {
        $linked_type = $this->_reflector->get_link_name($key);
        $linked_type_reflector = midcom_helper_reflector::get($linked_type);
        $field_type = $this->_reflector->get_midgard_type($key);

        if ($key == 'up') {
            $field_label = sprintf($this->_l10n->get('under %s'), midgard_admin_asgard_plugin::get_type_label($linked_type));
        } else {
            $type_label = midgard_admin_asgard_plugin::get_type_label($linked_type);
            if (substr($type_label, 0, strlen($key)) == $key) {
                // Handle abbreviations like "lang" for "language"
                $field_label = $type_label;
            } elseif ($key == $type_label) {
                $field_label = $key;
            } else {
                $ref = midcom_helper_reflector::get($this->_object);
                $component_l10n = $ref->get_component_l10n();
                $field_label = sprintf($this->_l10n->get('%s (%s)'), $component_l10n->get($key), $type_label);
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
                $component = midcom::get()->dbclassloader->get_component_for_class($linked_type);
                $searchfields = $linked_type_reflector->get_search_properties();
                $searchfields[] = 'guid';
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
                    'widget_config' => [
                        'class' => $class,
                        'component' => $component,
                        'titlefield' => $linked_type_reflector->get_label_property(),
                        'id_field' => $this->_reflector->get_link_target($key),
                        'searchfields' => $searchfields,
                        'result_headers' => $this->_get_result_headers($linked_type_reflector),
                        'orders' => [],
                        'creation_mode_enabled' => true,
                        'creation_handler' => midcom_connection::get_url('self') . "__mfa/asgard/object/create/chooser/{$linked_type}/",
                        'creation_default_key' => $linked_type_reflector->get_title_property(new $linked_type),
                        'categorize_by_parent_label' => true,
                        'get_label_for' => $linked_type_reflector->get_label_property(),
                    ],
                    'required' => (midgard_object_class::get_property_parent($this->_object->__mgdschema_class_name__) == $key)
                ];
                break;
        }
    }

    /**
     * Get headers to be used with chooser
     *
     * @return array
     */
    private function _get_result_headers($linked_type_reflector)
    {
        $headers = [];
        $properties = $linked_type_reflector->get_search_properties();
        $l10n = $linked_type_reflector->get_component_l10n();
        foreach ($properties as $property) {
            $headers[] = [
                'name' => $property,
                'title' => ucfirst($l10n->get($property)),
            ];
        }
        return $headers;
    }

    private function _add_copy_fields()
    {
        // Add switch for copying parameters
        $this->schema['fields']['parameters'] = [
            'title'       => $this->_l10n->get('copy parameters'),
            'storage'     => null,
            'type'        => 'boolean',
            'widget'      => 'checkbox',
            'default'     => true,
        ];

        // Add switch for copying metadata
        $this->schema['fields']['metadata'] = [
            'title'       => $this->_l10n->get('copy metadata'),
            'storage'     => null,
            'type'        => 'boolean',
            'widget'      => 'checkbox',
            'default'     => true,
        ];

        // Add switch for copying attachments
        $this->schema['fields']['attachments'] = [
            'title'       => $this->_l10n->get('copy attachments'),
            'storage'     => null,
            'type'        => 'boolean',
            'widget'      => 'checkbox',
            'default'     => true,
        ];

        // Add switch for copying privileges
        $this->schema['fields']['privileges'] = [
            'title'       => $this->_l10n->get('copy privileges'),
            'storage'     => null,
            'type'        => 'boolean',
            'widget'      => 'checkbox',
            'default'     => true,
        ];
    }

    private function _get_score($field)
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
        return (array_search($first, $fields) < array_search($second, $fields)) ? -1 : 1;
    }
}
