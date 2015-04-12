<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Experimental select type
 */
class radiocheckselect extends ChoiceType
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
        $map_multiple = function (Options $options)
        {
            return !empty($options['type_config']['allow_multiple']);
        };

        $resolver->setDefaults(array
        (
            'choices' => $map_options,
            'expanded' => true,
            'multiple' => $map_multiple,
            'placeholder' => false
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $view->vars['attr']['class'] = $options['multiple'] ? 'checkbox' : 'radiobox';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'radiocheckselect';
    }
}