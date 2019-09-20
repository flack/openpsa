<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager;

use midcom_error;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use midcom;
use midcom_core_context;
use midcom_services_i18n_l10n;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

/**
 * Experimental schema class
 */
class schema
{
    /**
     * @var array
     */
    private $defaults = [
        'operations' => ['save' => '', 'cancel' => ''],
        'fields' => [],
        'customdata' => [],
        'validation' => [],
        'action' => ''
    ];

    /**
     * @var array
     */
    private $config = [];

    /**
     * @var string
     */
    private $name = 'default';

    public function __construct(array $config)
    {
        $this->config = array_merge($this->defaults, $config);
        $this->complete_fields();
    }

    /**
     * @param string $name
     */
    public function set_name($name)
    {
        $this->name = $name;
    }

    public function get_name() : string
    {
        return $this->name;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->config[$key];
    }

    public function set($key, $value)
    {
        $this->config[$key] = $value;
        if ($key === 'fields') {
            $this->complete_fields();
        }
    }

    public function has_field($name) : bool
    {
        return array_key_exists($name, $this->config['fields']);
    }

    /**
     * Returns reference to field config (for on the fly modification)
     *
     * @param string $name
     * @return array
     */
    public function & get_field($name)
    {
        if (!$this->has_field($name)) {
            throw new \midcom_error('Field ' . $name . ' is not available in this schema');
        }
        return $this->config['fields'][$name];
    }

    public function get_defaults() : array
    {
        $defaults = [];
        foreach ($this->config['fields'] as $name => $config) {
            if (!empty($config['default'])) {
                $defaults[$name] = $config['default'];
            }
        }
        return $defaults;
    }

    public function get_l10n() : midcom_services_i18n_l10n
    {
        // Populate the l10n_schema member
        if (array_key_exists('l10n_db', $this->config)) {
            $l10n_name = $this->config['l10n_db'];
        } else {
            $l10n_name = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_COMPONENT);
        }
        if (!midcom::get()->componentloader->is_installed($l10n_name)) {
            $l10n_name = 'midcom';
        }
        return midcom::get()->i18n->get_l10n($l10n_name);
    }

    private function complete_fields()
    {
        foreach ($this->config['fields'] as $name => &$config) {
            $config = $this->resolve_field_options($config, $name);
        }
    }

    private function resolve_field_options(array $config, $name) : array
    {
        $resolver = new OptionsResolver();

        $resolver->setDefaults(array_merge([
            'title' => '',
            'description' => '',
            'type' => null,
            'type_config' => [],
            'widget' => null,
            'widget_config' => [],
            'required' => false,
            'readonly' => false,
            'hidden' => false,
            'default' => null,
            'storage' => '__UNSET__',
            'index_method' => 'auto',
            'index_merge_with_content' => true,
            'start_fieldset' => null,
            'end_fieldset' => null,
            'validation' => [],
            'helptext' => null,
            'write_privilege' => null,
            'customdata' => []
        ], $config));

        $normalize_widget = function (Options $options, $value) {
            if ($value == 'text') {
                if (!empty($options['widget_config']['hideinput'])) {
                    return PasswordType::class;
                }
                if ($options['type'] === 'number') {
                    return 'number';
                }
                if ($options['type'] === 'urlname') {
                    return 'urlname';
                }
                if (!empty($options['validation'])) {
                    foreach ($options['validation'] as $rule) {
                        if (is_array($rule) && $rule['type'] === 'email') {
                            return 'email';
                        }
                    }
                }
            }
            if ($value == 'select' && !empty($options['type_config']['allow_other'])) {
                return 'other';
            }
            return $value;
        };

        $normalize_storage = function (Options $options, $value) use ($name) {
            $default = [
                'location' => 'parameter',
                'domain' => 'midcom.helper.datamanager2',
                'name' => $name
            ];
            if ($value === '__UNSET__') {
                return $default;
            }
            if ($options['type'] === 'privilege') {
                return [
                    'location' => 'privilege',
                    'name' => $name
                ];
            }
            if ($options['type'] === 'tags') {
                return 'tags';
            }
            if ($value === null) {
                return null;
            }
            if (is_string($value)) {
                if ($value === 'metadata') {
                    return ['location' => $value, 'name' => $name];
                }
                if ($value === 'parameter') {
                    return $default;
                }
                return ['location' => $value];
            }
            if (strtolower($value['location']) === 'parameter') {
                $value['location'] = strtolower($value['location']);
                if (!array_key_exists('domain', $value)) {
                    $value['domain'] = 'midcom.helper.datamanager2';
                }
            }
            if (strtolower($value['location']) === 'configuration') {
                $value['location'] = 'parameter';
            }
            return $value;
        };

        $normalize_validation = function (Options $options, $value) {
            $validation = [];

            foreach ((array) $value as $key => $rule) {
                if (!is_array($rule)) {
                    if (is_object($rule)) {
                        $validation[] = $rule;
                        continue;
                    }
                    $rule = ['type' => $rule];
                } elseif (!array_key_exists('type', $rule)) {
                    throw new midcom_error("Missing validation rule type for rule {$key} on field {$options['name']}, this is a required option.");
                } elseif (   $rule['type'] == 'compare'
                          && !array_key_exists('compare_with', $rule)) {
                    throw new midcom_error("Missing compare_with option for compare type rule {$key} on field {$options['name']}, this is a required option.");
                }

                $defaults = [
                    'message' => "validation failed: {$rule['type']}",
                    'format' => ''
                ];

                $validation[] = array_merge($defaults, $rule);
            }

            return $validation;
        };

        $resolver->setNormalizer('storage', $normalize_storage);
        $resolver->setNormalizer('widget', $normalize_widget);
        $resolver->setNormalizer('validation', $normalize_validation);

        return $resolver->resolve($config);
    }
}
