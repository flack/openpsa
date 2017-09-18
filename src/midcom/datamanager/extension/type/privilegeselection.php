<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use midcom;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Experimental privilege type
 */
class privilegeselection extends privilege
{
    protected $defaultChoices = [
        'widget privilege: inherit' => MIDCOM_PRIVILEGE_INHERIT,
        'widget privilege: allow' => MIDCOM_PRIVILEGE_ALLOW,
        'widget privilege: deny' => MIDCOM_PRIVILEGE_DENY,
    ];

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('expanded', false);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $head = midcom::get()->head;
        $head->enable_jquery();
        $head->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.datamanager/privilege/jquery.privilege.css');
        $head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.datamanager/privilege/jquery.privilege.js');
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $effective_value = $this->get_effective_value($options['type_config'], $options['storage']->get_value()) ? 'allow' : 'deny';
        $view->vars['jsinit'] = '$("#' . $view->vars['id'] . '").parent().render_privilege({effective_value: "' . $effective_value . '"});';
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'privilegeselection';
    }
}