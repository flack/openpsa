<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Form\Extension\Core\Type\FormType as base;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Callback;
use midcom\datamanager\storage\container\dbacontainer;
use midcom\datamanager\validation\callback as cb_wrapper;
use midcom\datamanager\extension\compat;
use midcom;
use midcom\datamanager\schema;

/**
 * Experimental form type
 */
class formType extends base
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

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
            return [];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        if (!array_key_exists('data', $options)) {
            // This happens when we are in the nested case
            // @todo Figure out why
            $options['data'] = null;
        }
        $storage = $options['data'];

        foreach ($options['schema']->get('fields') as $field => $config) {
            if ($config['widget'] === 'csrf') {
                continue;
            }

            if ($config['write_privilege'] !== null) {
                if (   array_key_exists('group', $config['write_privilege'])
                    && !midcom::get()->auth->is_group_member($config['write_privilege']['group'])) {
                    $config['readonly'] = true;
                }
                if (   array_key_exists('privilege', $config['write_privilege'])
                    && $storage instanceof dbacontainer
                    && !$storage->get_value()->can_do($config['write_privilege']['privilege'])) {
                    $config['readonly'] = true;
                }
            }

            $settings = [
                'label' => $config['title'],
                'widget_config' => $config['widget_config'],
                'type_config' => $config['type_config'],
                'required' => $config['required'],
                'constraints' => $config['validation'],
                'dm2_type' => $config['type'],
                'dm2_storage' => $config['storage'],
                'start_fieldset' => $config['start_fieldset'],
                'end_fieldset' => $config['end_fieldset'],
                'index_method' => $config['index_method'],
                'attr' => ['readonly' => $config['readonly']],
                'helptext' => $config['helptext'],
                'storage' => $storage,
                'hidden' => $config['hidden']
            ];

            $builder->add($field, compat::get_type_name($config['widget']), $settings);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return base::class;
    }
}
