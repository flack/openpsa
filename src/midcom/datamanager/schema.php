<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager;

use midcom_error;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use midcom;
use midcom_core_context;
use midcom\datamanager\extension\compat;

/**
 * Experimental schema class
 */
class schema
{
    private $defaults = [
        'operations' => ['save' => '', 'cancel' => '']
    ];

    private $config = [];

    /**
     *
     * @var string
     */
    private $name = 'default';

    public function __construct(array $config)
    {
        $this->config = array_merge($this->defaults, $config);
        $this->complete_fields();
    }

    /**
     *
     * @param string $name
     */
    public function set_name($name)
    {
        $this->name = $name;
    }

    /**
     *
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }

    /**
     *
     * @param FormBuilderInterface $builder
     * @return \Symfony\Component\Form\Form
     */
    public function build_form(FormBuilderInterface $builder)
    {
        foreach ($this->config['fields'] as $name => $config) {
            $options = [
                'label' => $config['title'],
                'widget_config' => $config['widget_config'],
                'type_config' => $config['type_config'],
                'required' => $config['required'],
                'constraints' => $config['required'] ? [new NotBlank()] : null,
                'dm2_type' => $config['type'],
                'start_fieldset' => $config['start_fieldset'],
                'end_fieldset' => $config['end_fieldset'],
                'index_method' => $config['index_method'],
                'attr' => ['readonly' => $config['readonly']]
            ];

            $builder->add($name, compat::get_type_name($config['widget']), $options);
        }

        $builder->add('form_toolbar', compat::get_type_name('toolbar'), ['operations' => $this->config['operations']]);
        return $builder->getForm();
    }

    /**
     *
     * @return array
     */
    public function get_fields()
    {
        return $this->config['fields'];
    }

    /**
     *
     * @return \midcom_services_i18n_l10n
     */
    public function get_l10n()
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

    private function resolve_field_options(array $config, $name)
    {
        $resolver = new OptionsResolver();

        $resolver->setDefaults([
            'title' => '',
            'type' => null,
            'type_config' => [],
            'widget' => null,
            'widget_config' => [],
            'required' => false,
            'readonly' => false,
            'default' => null,
            'storage' => '__UNSET__',
            'index_method' => 'auto',
            'start_fieldset' => null,
            'end_fieldset' => null,
            'validation' => []
        ]);

        $normalize_widget = function (Options $options, $value) {
            if (   $value == 'images'
                || $value == 'downloads') {
                return 'subform';
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
            if ($value === null) {
                if ($options['type'] === 'privilege') {
                    return [
                        'location' => 'privilege',
                        'name' => $name
                    ];
                }
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

        $normalize_validation = function (Options $options, $config) {
            $validation = [];
            if (array_key_exists('validation', (array) $config)) {
                $validation = (array) $config['validation'];
            }

            foreach ($validation as $key => $rule) {
                if (!is_array($rule)) {
                    $rule = ['type' => $rule];
                } elseif (!array_key_exists('type', $rule)) {
                    throw new midcom_error("Missing validation rule type for rule {$key} on field {$config['name']}, this is a required option.");
                } elseif (   $rule['type'] == 'compare'
                          && !array_key_exists('compare_with', $rule)) {
                    throw new midcom_error("Missing compare_with option for compare type rule {$key} on field {$config['name']}, this is a required option.");
                }

                $defaults = [
                    'message' => "validation failed: {$rule['type']}",
                    'format' => ''
                ];

                $validation[$key] = array_merge($defaults, $rule);
            }
            return $validation;
        };

        $resolver->setNormalizer('storage', $normalize_storage);
        $resolver->setNormalizer('widget', $normalize_widget);
        $resolver->setNormalizer('validation', $normalize_validation);

        return $resolver->resolve($config);
    }
}
