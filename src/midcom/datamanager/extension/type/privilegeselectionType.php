<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use midcom;
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
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('expanded', false);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $effective_value = $view->vars['effective_value'] ? 'allow' : 'deny';
        $view->vars['jsinit'] = '$("#' . $view->vars['id'] . '").parent().render_privilege({effective_value: "' . $effective_value . '"});';

        $head = midcom::get()->head;
        $head->enable_jquery();
        $head->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.datamanager/privilege/jquery.privilege.css');
        $head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.datamanager/privilege/jquery.privilege.js');
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'privilegeselection';
    }

    public function getParent()
    {
        return privilegeType::class;
    }
}