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
use DateTime;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

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
        $resolver->setNormalizer('widget_config', function (Options $options, $value)
        {
            $widget_defaults = array
            (
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
        $builder->add('file', 'file');
        if ($options['widget_config']['show_title'])
        {
            $builder->add('title', 'text');
        }
        if ($options['widget_config']['show_description'])
        {
            $builder->add('description', 'text');
        }
        $builder->add('identifier', 'hidden', array('data' => 'file'));
        if ($options['widget_config']['sortable'])
        {
            $builder->add('score', 'hidden');
        }
        $builder->addViewTransformer(new transformer($options));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'images';
    }
}