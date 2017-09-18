<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Form\AbstractType;
use midcom\datamanager\extension\transformer\other as transformer;
use midcom\datamanager\extension\transformer\multiple;
use midcom\datamanager\extension\helper;
use midcom\datamanager\extension\compat;

/**
 * Experimental other type
 */
class other extends AbstractType
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
                'allow_other' => false,
                'allow_multiple' => ($options['dm2_type'] == 'mnrelation'),
                'require_corresponding_option' => true,
                'multiple_storagemode' => 'serialized',
                'multiple_separator' => '|'
            ];
            return helper::resolve_options($type_defaults, $value);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new transformer($options['type_config']['options']));
        if (!empty($options['type_config']['allow_multiple'])) {
            $builder->addModelTransformer(new multiple($options));
        }
        $builder->add('select', compat::get_type_name('select'), [
            'type_config' => $options['type_config'],
            'widget_config' => $options['widget_config'],
        ]);
        $builder->add('other', compat::get_type_name('text'), ['label' => 'widget select: other value']);
    }


    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'other';
    }
}
