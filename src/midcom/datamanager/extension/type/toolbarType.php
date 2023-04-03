<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use midcom\datamanager\storage\container\dbacontainer;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use midcom\datamanager\controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Experimental toolbar type
 */
class toolbarType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'operations' => [],
            'mapped' => false
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($options['operations'] as $operation => $button_labels) {
            foreach ((array) $button_labels as $key => $label) {
                $attributes = [
                    'operation' => $operation,
                    'label' => $label,
                    'attr' => ['class' => 'submit ' . $operation]
                ];
                if ($operation == controller::SAVE) {
                    $attributes['attr']['accesskey'] = 's';
                    $attributes['attr']['class'] .= ' save_' . $key;
                } elseif ($operation == controller::CANCEL) {
                    $attributes['attr']['accesskey'] = 'd';
                    $attributes['attr']['formnovalidate'] = true;
                } elseif ($operation == controller::PREVIOUS) {
                    $attributes['attr']['formnovalidate'] = true;
                }

                $builder->add($operation . $key, SubmitType::class, $attributes);
            }
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['button-labels'] = [];
        foreach ($form->all() as $key => $button) {
            $label = $button->getConfig()->getOption('label');
            if (!$label) {
                $operation = $button->getConfig()->getOption('operation');
                $storage = $view->parent->vars['value'];

                if (   $operation == controller::SAVE
                    && $storage instanceof dbacontainer
                    && empty($storage->get_value()->id)) {
                    $label = 'create';
                } else {
                    $label = "form submit: {$operation}";
                }
            }
            $view->vars['button-labels'][$key] = $label;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'toolbar';
    }
}
