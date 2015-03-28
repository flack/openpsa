<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;

/**
 * Experimental select type
 */
class select extends ChoiceType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $map_options = function (Options $options)
        {
            if (isset($options['type_config']['options']))
            {
                return $options['type_config']['options'];
            }
        };

        $resolver->setDefaults(array
        (
            'choices' => $map_options
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'select';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }
}