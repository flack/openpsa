<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;
use midcom\datamanager\extension\compat;


/**
 * Experimental select type
 */
class select extends ChoiceType
{
    /**
     *  Symfony 2.6 compat
     *
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->configureOptions($resolver);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $map_options = function (Options $options) {
            $return_options = array();

            if (isset($options['type_config']['options'])) {
                foreach ($options['type_config']['options'] as $key => $value) {
                    //symfony expects only string
                    $return_options[$value] = (string)$key;
                }
                return $return_options;
            }
        };

        $multiple_options = function (Options $options)
        {
            $return_options = array();

            if(isset($options['type_config']['allow_multiple'])){
                $return_options[] = true;
            }
            return $return_options;
        };

        $resolver->setDefaults(array(
            'choices' => $map_options,
            'choices_as_values' => true,
            'multiple' => $multiple_options,
        ));
    }

    /**
     * {@inheritdoc}
     *
     * Symfony < 2.8 compat
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'select';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return compat::get_type_name('choice');
    }
}
