<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\Form\Extension\Core\Type\TextType as base;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;
use midcom\datamanager\extension\helper;
use midcom\datamanager\validation\pattern as validator;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\FormBuilderInterface;
use midcom\datamanager\extension\subscriber\purifySubscriber;

/**
 * Experimental textarea type
 */
class textType extends base
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('constraints', []);
        $resolver->setNormalizer('type_config', function (Options $options, $value) {
            $type_defaults = [
                'forbidden_patterns' => [],
                'maxlength' => 0,
                'purify' => false,
                'purify_config' => []
            ];
            return helper::resolve_options($type_defaults, $value);
        });
        $resolver->setNormalizer('constraints', function (Options $options, $value) {
            if (!empty($options['type_config']['forbidden_patterns'])) {
                $value[] = new validator(['forbidden_patterns' => $options['type_config']['forbidden_patterns']]);
            }
            if (!empty($options['type_config']['maxlength'])) {
                $value[] = new Length(['max' => $options['type_config']['maxlength']]);
            }
            return $value;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        if ($options['type_config']['purify']) {
            $builder->addEventSubscriber(new purifySubscriber($options['type_config']['purify_config']));
        }
    }
}
