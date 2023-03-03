<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

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
    private \midcom_services_i18n_l10n $l10n;

    public function __construct(\midcom_services_i18n $i18n)
    {
        $this->l10n = $i18n->get_l10n('midcom.datamanager');
    }

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
                    'label' => $this->l10n->get($label),
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
