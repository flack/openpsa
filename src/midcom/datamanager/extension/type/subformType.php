<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;
use midcom\datamanager\extension\helper;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Form\AbstractType;

/**
 * Subform type
 */
class subformType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'allow_add' => true,
            'allow_delete' => true,
            'prototype' => true,
            'prototype_name' => '__name__',
            'delete_empty' => true,
            'error_bubbling' => false,
        ]);
        helper::add_normalizers($resolver, [
            'type_config' => [
                'sortable' => false,
                'max_count' => 0
            ]
        ]);
        $resolver->setNormalizer('constraints', function (Options $options, $value) {
            $validation = [];
            if ($options['type_config']['max_count'] > 0) {
                $validation['max'] = $options['type_config']['max_count'];
            }
            if ($options['required']) {
                $validation['min'] = 1;
            }
            if (!empty($validation)) {
                return [new Count($validation)];
            }
            return $validation;
        });
        $resolver->setNormalizer('entry_options', function (Options $options, $value) {
            return array_replace([
                'widget_config' => $options['widget_config']
            ], (array) $value);
        });
        $resolver->setNormalizer('widget_config', function (Options $options, $value) {
            if (!array_key_exists('sortable', $value)) {
                $value['sortable'] = false;
            }
            return $value;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['max_count'] = $options['type_config']['max_count'];
        $view->vars['sortable'] = ($options['widget_config']['sortable']) ? 'true' : 'false';
        $view->vars['allow_add'] = $options['allow_add'] ? 'true' : 'false';
        $view->vars['allow_delete'] = $options['allow_delete'] ? 'true' : 'false';
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'subform';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CollectionType::class;
    }
}
