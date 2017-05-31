<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use midcom\datamanager\extension\compat;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use midcom;
use midcom_core_user;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use midcom\datamanager\storage\container\dbacontainer;

/**
 * Experimental privilege type
 */
class privilege extends RadioType
{
    private $defaultChoices = array(
        'widget privilege: allow' => MIDCOM_PRIVILEGE_ALLOW,
        'widget privilege: deny' => MIDCOM_PRIVILEGE_DENY,
        'widget privilege: inherit' => MIDCOM_PRIVILEGE_INHERIT,
    );

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
        $map_privilege = function (Options $options) {
            $return_options = $this->defaultChoices;
            return $return_options;
        };
        $resolver->setDefaults(array(
            'choices' => $map_privilege,
            'choices_as_values' => true,
            'expanded' => true,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $view->vars['type'] = $this;
        $view->vars['type_conf'] = $options['type_config'];
    }

    public function get_effective_value(array $options, $object = null)
    {
        if (!$object)
        {
            $defaults = midcom::get()->auth->acl->get_default_privileges();
            return $defaults[$options['privilege_name']] === MIDCOM_PRIVILEGE_ALLOW;
        }
        if ($options['assignee'] == 'SELF') {
            if ($object instanceof \midcom_db_group) {
                //There's no sane way to query group privileges in auth right now, so we only return defaults
                $defaults = midcom::get()->auth->acl->get_default_privileges();
                return (($defaults[$options['privilege_name']] === MIDCOM_PRIVILEGE_ALLOW));
            }
            return midcom::get()->auth->can_user_do($options['privilege_name'],
                    new midcom_core_user($object->__object->id), $options['classname']);
        }
        if ($principal = midcom::get()->auth->get_assignee($options['assignee'])) {
            return $object->can_do($options['privilege_name'], $principal);
        }
        return $object->can_do($options['privilege_name'], $options['assignee']);
    }

    public function render_choices(array $options, $object = null)
    {
        $l10n = midcom::get()->i18n->get_l10n('midcom.datamanager');

        if ($this->get_effective_value($options, $object)) {
            $label = $l10n->get('widget privilege: allow');
        } else {
            $label = $l10n->get('widget privilege: deny');
        }
        return sprintf($l10n->get('widget privilege: inherit %s'), $label);
    }

    public function search_for_object($object)
    {
        while (true)
        {
            if ($object instanceof dbacontainer) {
                return $object->get_value();
            }
            if (!empty($object->parent)) {
                $object = $object->parent->vars['data'];
            } else {
                return null;
            }
        }
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
        return 'privilege';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return compat::get_type_name('radiocheckselect');
    }
}