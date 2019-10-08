<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use midcom;
use midcom\datamanager\controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

/**
 * Experimental autocomplete type
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
            'mapped' => false,
            'is_create' => false
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $l10n = midcom::get()->i18n->get_l10n('midcom.datamanager');
        foreach ($options['operations'] as $operation => $button_labels) {
            foreach ((array) $button_labels as $key => $label) {
                if ($label == '') {
                    if ($operation == 'save' && $options['is_create']) {
                        $label = 'create';
                    } else {
                        $label = "form submit: {$operation}";
                    }
                }
                $attributes = [
                    'operation' => $operation,
                    'label' => $l10n->get($label),
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

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'toolbar';
    }
}
