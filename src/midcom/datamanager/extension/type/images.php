<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Form\AbstractType;
use midcom\datamanager\extension\helper;
use midcom\datamanager\extension\transformer\blobs as transformer;
use midcom;
use midcom\datamanager\extension\compat;

/**
 * Experimental image type
 */
class images extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setNormalizer('widget_config', function (Options $options, $value) {
            $widget_defaults = array(
                'map_action_elements' => false,
                'show_title' => true,
                'show_description' => false,
                'sortable' => false
            );
            return helper::resolve_options($widget_defaults, $value);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('file', compat::get_type_name('file'));
        if ($options['widget_config']['show_title']) {
            $builder->add('title', compat::get_type_name('text'));
        }
        if ($options['widget_config']['show_description']) {
            $builder->add('description', compat::get_type_name('text'));
        }
        $builder->add('identifier', compat::get_type_name('hidden'), array('data' => 'file'));
        if ($options['widget_config']['sortable']) {
            $builder->add('score', compat::get_type_name('hidden'));
        }
        $builder->addViewTransformer(new transformer($options));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'images';
    }
}
