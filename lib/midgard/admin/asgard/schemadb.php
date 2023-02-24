<?php
/**
 * @package midgard.admin.asgard
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\schemabuilder;

/**
 * Helper class to create a DM schema from an object via reflection
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_schemadb extends schemabuilder
{
    private midcom_helper_configuration $_config;

    private midcom_services_i18n_l10n $l10n;

    public bool $add_copy_fields = false;

    public function __construct(midcom_core_dbaobject $object, midcom_helper_configuration $config)
    {
        parent::__construct($object);
        $this->_config = $config;
        $this->l10n = midcom::get()->i18n->get_l10n('midgard.admin.asgard');
    }

    /**
     * Generates, loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    protected function process_type(string $type, array $type_fields)
    {
        usort($type_fields, [$this, 'sort_schema_fields']);

        parent::process_type($type, $type_fields);

        $this->_add_rcs_field();

        if ($this->add_copy_fields) {
            $this->_add_copy_fields();
        }
        if (empty($this->schema['l10n_db'])) {
            $this->schema['l10n_db'] = 'midgard.admin.asgard';
        }
    }

    protected function add_string_field(string $key, string $type)
    {
        if (   $key == 'component'
            && $type == midcom_db_topic::class) {
            $this->_add_component_dropdown($key);
            return;
        }

        // Special name handling, start by checking if given type is same as $this->object and if not making a dummy copy (we're probably in creation mode then)
        if ($this->object instanceof $type) {
            $name_obj = $this->object;
        } else {
            $name_obj = new $type();
        }

        if ($key === midcom_helper_reflector::get_name_property($name_obj)) {
            $this->_add_name_field($key, $name_obj);
            return;
        }
        parent::add_string_field($key, $type);
    }

    private function _add_name_field(string $key, midcom_core_dbaobject $name_obj)
    {
        $type_urlname_config = [];
        $allow_unclean_name_types = $this->_config->get_array('allow_unclean_names_for');
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
            if (!$manifest->purecode) {
                $components[$manifest->name] = $manifest->get_name_translated() . " ({$manifest->name})";
            }
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

    protected function add_longtext_field(string $key)
    {
        parent::add_longtext_field($key);

        // Check the user preference and configuration
        if (   in_array($key, ['content', 'description'])
            && midgard_admin_asgard_plugin::get_preference('tinymce_enabled')) {
            $this->schema['fields'][$key]['widget'] = 'tinymce';
        }

        if (   in_array($key, ['value', 'code'])
            && midgard_admin_asgard_plugin::get_preference('codemirror_enabled')) {
            $this->schema['fields'][$key]['widget'] = 'codemirror';
        }
    }

    protected function add_linked_field(string $key)
    {
        parent::add_linked_field($key);

        $linked_type = $this->reflector->get_link_name($key);
        $type_label = midcom_helper_reflector::get($linked_type)->get_class_label();

        if ($key == 'up') {
            $field_label = sprintf($this->l10n->get('under %s'), $type_label);
        } else {
            $field_label = sprintf($this->l10n->get('%s (%s)'), $this->schema['fields'][$key]['title'], $type_label);
        }
        $this->schema['fields'][$key]['title'] = $field_label;

        $this->schema['fields'][$key]['widget_config']['creation_mode_enabled'] = true;
        $this->schema['fields'][$key]['widget_config']['creation_handler'] = midcom_connection::get_url('self') . "__mfa/asgard/object/create/chooser/{$linked_type}/";
        $this->schema['fields'][$key]['widget_config']['creation_default_key'] = midcom_helper_reflector::get_title_property(new $linked_type);
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
        $preferred_fields = $this->_config->get_array('object_preferred_fields');
        $timerange_fields = $this->_config->get_array('object_timerange_fields');
        $phone_fields = $this->_config->get_array('object_phone_fields');
        $address_fields = $this->_config->get_array('object_address_fields');
        $location_fields = $this->_config->get_array('object_location_fields');

        $score = 7;

        if ($this->reflector->get_midgard_type($field) == MGD_TYPE_LONGTEXT) {
            $score = 1;
        } elseif (in_array($field, $preferred_fields)) {
            $score = 0;
        } elseif ($this->reflector->is_link($field)) {
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

    public function sort_schema_fields(string $first, string $second) : int
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
        $fields = $this->_config->get_array('object_' . $type . '_fields');
        return array_search($first, $fields) <=> array_search($second, $fields);
    }
}
