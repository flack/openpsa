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
use midcom\datamanager\validation\callback as cb_wrapper;
use midcom\datamanager\extension\compat;
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

            $builder->add($field, compat::get_type_name($config['widget']), compat::get_settings($config, $storage));
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
