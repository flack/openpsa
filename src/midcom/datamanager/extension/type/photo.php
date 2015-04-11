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
use midcom;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use midcom\datamanager\extension\transformer\photo as transformer;
use midcom\datamanager\validation\photo as constraint;

/**
 * Experimental photo type
 */
class photo extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array
        (
            'error_bubbling' => false
        ));
        $resolver->setNormalizers(array
        (
            'widget_config' => function (Options $options, $value)
            {
                $widget_defaults = array
                (
                    'map_action_elements' => false,
                    'show_title' => false
                );
                return helper::resolve_options($widget_defaults, $value);
            },
            'constraints' => function (Options $options, $value)
            {
                if ($options['required'])
                {
                    return array(new constraint());
                }
                return array();
            }
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(new transformer($options));
        $builder->add('file', 'file', array('required' => false));
        if ($options['widget_config']['show_title'])
        {
            $builder->add('title', 'text');
        }
        $builder->add('identifier', 'hidden', array('data' => 'file'));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'photo';
    }
}