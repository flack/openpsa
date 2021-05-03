<?php
/**
 * @package midgard.admin.asgard
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\datamanager;

use midcom_core_dbaobject;
use midcom_helper_reflector;
use midgard_reflection_property;
use midgard_object_class;
use midcom;

/**
 * Helper class to create a DM schema from an object via reflection
 *
 * @package midgard.admin.asgard
 */
class schemabuilder
{
    /**
     * The object we're working with
     *
     * @var midcom_core_dbaobject
     */
    protected $object;

    /**
     * The schema in use
     *
     * @var array
     */
    protected $schema;

    /**
     * Midgard reflection property instance for the current object's class.
     *
     * @var midgard_reflection_property
     */
    protected $reflector;

    public function __construct(midcom_core_dbaobject $object)
    {
        $this->object = $object;
        $this->reflector = new midgard_reflection_property(midcom_helper_reflector::resolve_baseclass($this->object));
    }

    /**
     * Generates, loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    public function create($include_fields) : schemadb
    {
        $type = get_class($this->object);
        $type_fields = $this->object->get_properties();

        $this->schema = [
            'description' => 'object schema',
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

        $this->process_type($type, $type_fields);

        return new schemadb(['default' => $this->schema]);
    }

    protected function process_type(string $type, array $type_fields)
    {
        // Iterate through object properties
        foreach ($type_fields as $key) {
            // Linked fields should use chooser
            if ($this->reflector->is_link($key)) {
                $this->add_linked_field($key);
                // Skip rest of processing
                continue;
            }

            $field_type = $this->reflector->get_midgard_type($key);
            switch ($field_type) {
                case MGD_TYPE_GUID:
                case MGD_TYPE_STRING:
                    $this->add_string_field($key, $type);
                    break;
                case MGD_TYPE_LONGTEXT:
                    $this->add_longtext_field($key);
                    break;
                case MGD_TYPE_INT:
                case MGD_TYPE_UINT:
                    $this->add_int_field($key);
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
    }

    private function _filter_schema_fields(string $key) : bool
    {
        return !in_array($key, ['id', 'guid', 'metadata']);
    }

    protected function add_string_field(string $key, string $type)
    {
        $this->schema['fields'][$key] = [
            'title'       => $key,
            'storage'     => $key,
            'type'        => 'text',
            'widget'      => 'text',
        ];
    }

    protected function add_int_field(string $key)
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

    protected function add_longtext_field(string $key)
    {
        // Figure out nice size for the editing field
        $output_mode = '';
        $widget = 'textarea';
        $dm_type = 'text';

        switch ($key) {
            case 'content':
            case 'description':
                $height = 12;
                $output_mode = 'html';
                break;

            case 'value':
            case 'code':
                // These are typical "large" fields
                $height = 12;
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

    protected function add_linked_field(string $key)
    {
        $linked_type = $this->reflector->get_link_name($key);
        $field_type = $this->reflector->get_midgard_type($key);
        $type_label = midcom_helper_reflector::get($linked_type)->get_class_label();

        if (str_starts_with($type_label, $key)) {
            // Handle abbreviations like "lang" for "language"
            $field_label = $type_label;
        } elseif ($key == $type_label) {
            $field_label = $key;
        } else {
            $l10n = midcom_helper_reflector::get($this->object)->get_component_l10n();
            $field_label = $l10n->get($key);
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
                    'required' => (midgard_object_class::get_property_parent($this->object->__mgdschema_class_name__) == $key)
                ];
                break;
        }
    }

    private function build_autocomplete_config(string $key, string $class, string $linked_type) : array
    {
        $reflector = midcom_helper_reflector::get($linked_type);
        $searchfields = $reflector->get_search_properties();
        $label_property = $reflector->get_label_property();
        $has_parent = !empty(midgard_object_class::get_property_parent($linked_type)) || !empty(midgard_object_class::get_property_up($linked_type));
        $result_headers = [];

        foreach ($searchfields as $field) {
            if ($field !== $label_property) {
                $result_headers[] = ['name' => $field];
            }
        }
        $searchfields[] = 'guid';

        return [
            'class' => $class,
            'titlefield' => method_exists($class, 'get_label') ? null : $label_property,
            'id_field' => $this->reflector->get_link_target($key),
            'searchfields' => $searchfields,
            'result_headers' => $result_headers,
            'orders' => [],
            'categorize_by_parent_label' => $has_parent
        ];
    }
}
