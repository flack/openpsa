<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setNormalizers(array
        (
            'widget_config' => function (Options $options, $value)
            {
                $widget_defaults = array
                (
                    'map_action_elements' => false,
                    'show_title' => true
                );
                return helper::resolve_options($widget_defaults, $value);
            },
        ));
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
        $builder->add('identifier', 'hidden', array('data' => 'file'));
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