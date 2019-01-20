<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Callback;
use midcom\datamanager\validation\callback as cb_wrapper;
use midcom\datamanager\schema;
use midcom_error;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\AbstractType;

/**
 * Schema form type
 */
class schemaType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired('schema')
            ->setAllowedTypes('schema', schema::class)
            ->setDefault('action', function (Options $options, $value) {
                return $options['schema']->get('action');
            });

        $resolver->setNormalizer('csrf_protection', function (Options $options, $value) {
            foreach ($options['schema']->get('fields') as $config) {
                if ($config['widget'] === 'csrf') {
                    return true;
                }
            }
            return false;
        });
        $resolver->setNormalizer('constraints', function (Options $options, $value) {
            $validation = $options['schema']->get('validation');
            if (!empty($validation)) {
                $cb_wrapper = new cb_wrapper($validation);
                return [new Callback(['callback' => [$cb_wrapper, 'validate']])];
            }
            return $value;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($options['schema']->get('fields') as $field => $config) {
            if ($config['widget'] === 'csrf') {
                continue;
            }

            $builder->add($field, $this->get_type_name($config['widget']), $this->get_settings($config));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'form';
    }

    /**
     * Provide fully qualified type names
     *
     * @param string $shortname
     * @return string
     */
    private function get_type_name($shortname)
    {
        if (class_exists('midcom\datamanager\extension\type\\' . $shortname . 'Type')) {
            return 'midcom\datamanager\extension\type\\' . $shortname . 'Type';
        }
        if (class_exists('Symfony\Component\Form\Extension\Core\Type\\' . ucfirst($shortname) . 'Type')) {
            return 'Symfony\Component\Form\Extension\Core\Type\\' . ucfirst($shortname) . 'Type';
        }
        return $shortname;
    }

    /**
     * Convert schema config to type settings
     *
     * @param array $config
     * @return array
     */
    private function get_settings(array $config)
    {
        $settings = $config;
        $settings['label'] = $config['title'];
        $settings['dm2_type'] = $config['type'];
        $settings['constraints'] = $this->build_constraints($config);

        $remove = ['type', 'customdata', 'default', 'description', 'title', 'validation', 'widget'];
        return array_diff_key($settings, array_flip($remove));
    }

    private function build_constraints($config)
    {
        $constraints = !empty($config['constraints']) ? $config['constraints'] : [];

        foreach ((array) $config['validation'] as $rule) {
            if (is_object($rule)) {
                $constraints[] = $rule;
                continue;
            }
            if ($rule['type'] === 'email') {
                $constraints[] = new Email();
            } elseif ($rule['type'] === 'regex') {
                $r_options = ['pattern' => $rule['format']];
                if (!empty($rule['message'])) {
                    $r_options['message'] = $rule['message'];
                }
                $constraints[] = new Regex($r_options);
            } else {
                throw new midcom_error($rule['type'] . ' validation not implemented yet');
            }
        }
        if ($config['required']) {
            array_unshift($constraints, new NotBlank());
        }

        return $constraints;
    }
}
