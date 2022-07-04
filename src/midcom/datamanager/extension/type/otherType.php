<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Form\AbstractType;
use midcom\datamanager\extension\transformer\otherTransformer;
use midcom\datamanager\extension\transformer\multipleTransformer;
use midcom\datamanager\extension\helper;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Select + alternative text input type
 */
class otherType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('error_bubbling', false);
        $resolver->setNormalizer('type_config', function (Options $options, $value) {
            $type_defaults = [
                'options' => [],
                'allow_other' => true,
                'allow_multiple' => ($options['dm2_type'] == 'mnrelation'),
                'require_corresponding_option' => true,
                'multiple_storagemode' => 'serialized',
                'multiple_separator' => '|'
            ];
            return helper::normalize($type_defaults, $value);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new otherTransformer($options['type_config']['options']));
        if (!empty($options['type_config']['allow_multiple'])) {
            $builder->addModelTransformer(new multipleTransformer($options));
        }
        $builder->add('select', selectType::class, [
            'type_config' => $options['type_config'],
            'widget_config' => $options['widget_config'],
        ]);
        $builder->add('other', TextType::class, ['label' => 'widget select: other value']);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'other';
    }
}
