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
use midcom\datamanager\extension\transformer\jsdate as jsdatetransformer;
use midcom;
use DateTime;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use midcom\datamanager\extension\transformer\blobs as transformer;

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
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(new transformer($options));
        $builder->add('photo', 'file');
        $builder->add('identifier', 'hidden', array('data' => 'photo'));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'photo';
    }
}