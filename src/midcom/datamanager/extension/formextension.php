<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Experimental extension class
 */
class formextension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'widget_config' => array(),
            'type_config' => array(),
            'dm2_type' => null,
            'index_method' => 'auto',
            'index_merge_with_content' => true,
            'start_fieldset' => null,
            'end_fieldset' => null
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['start_fieldset'] = $options['start_fieldset'];
        $view->vars['end_fieldset'] = $options['end_fieldset'];
        $view->vars['index_method'] = $options['index_method'];
        $view->vars['index_merge_with_content'] = $options['index_merge_with_content'];
    }

    public function getExtendedType()
    {
        return compat::get_type_name('form');
    }
}
