<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;

/**
 * Experimental privilege type
 */
class privilegeselectionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver) : void
    {
        $resolver->setDefault('expanded', false);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options) : void
    {
        $effective_value = $view->vars['effective_value'] ? 'allow' : 'deny';
        $view->vars['jsinit'] = '$("#' . $view->vars['id'] . '").parent().render_privilege({effective_value: "' . $effective_value . '"});';
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix() : string
    {
        return 'privilegeselection';
    }

    public function getParent() : ?string
    {
        return privilegeType::class;
    }
}