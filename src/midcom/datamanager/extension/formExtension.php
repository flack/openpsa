<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\OptionsResolver\Options;
use midcom;
use midcom\datamanager\storage\container\dbacontainer;

/**
 * Experimental extension class
 */
class formExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'widget_config' => [],
            'type_config' => [],
            'dm2_type' => null,
            'dm2_storage' => null,
            'index_method' => 'auto',
            'index_merge_with_content' => true,
            'start_fieldset' => null,
            'end_fieldset' => null,
            'helptext' => null,
            'storage' => null,
            'hidden' => false,
            'readonly' => false,
            'write_privilege' => null,
            'disabled' => function(Options $options) {
                return !empty($options['hidden']);
            }
        ]);
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
        $view->vars['hidden'] = $options['hidden'];
        $view->vars['readonly'] = $options['readonly'];

        if ($options['write_privilege'] !== null) {
            if (   array_key_exists('group', $options['write_privilege'])
                && !midcom::get()->auth->is_group_member($options['write_privilege']['group'])) {
                $view->vars['readonly'] = true;
            }
            if (array_key_exists('privilege', $options['write_privilege'])) {
                $storage = $form->getParent()->getData();
                if (   $storage instanceof dbacontainer
                    && (true || !$storage->get_value()->can_do($options['write_privilege']['privilege']))) {
                    $view->vars['readonly'] = true;
                }
            }
        }

    }

    // Symfony < 4.2 compat
    public function getExtendedType()
    {
        return FormType::class;
    }

    public static function getExtendedTypes()
    {
        return [FormType::class];
    }
}
