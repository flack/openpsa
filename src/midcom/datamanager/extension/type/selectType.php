<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;
use midcom\datamanager\extension\transformer\multipleTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use midcom\datamanager\extension\helper;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use midcom\datamanager\extension\choicelist\loader;
use Symfony\Component\Form\AbstractType;

/**
 * Select type
 */
class selectType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $choice_loader = function (Options $options) {
            if (!empty($options['choices'])) {
                return null;
            }
            return new loader($options['type_config']);
        };

        $map_multiple = function (Options $options) {
            return !empty($options['type_config']['allow_multiple']);
        };

        $resolver->setDefaults([
            'choice_loader' => $choice_loader,
            'multiple' => $map_multiple,
            'placeholder' => false
        ]);

        $resolver->setNormalizer('type_config', function (Options $options, $value) {
            $type_defaults = [
                'options' => [],
                'option_callback' => null,
                'option_callback_arg' => null,
                'allow_other' => false,
                'allow_multiple' => ($options['dm2_type'] == 'mnrelation'),
                'require_corresponding_option' => true,
                'multiple_storagemode' => 'serialized',
                'multiple_separator' => '|'
            ];
            return helper::normalize($type_defaults, $value);
        });
        helper::add_normalizers($resolver, [
            'widget_config' => [
                'height' => 6,
                'jsevents' => []
            ]
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['multiple'] && !empty($options['dm2_type']) && $options['dm2_type'] != 'mnrelation') {
            $builder->addModelTransformer(new multipleTransformer($options));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if ($options['multiple']) {
            $view->vars['attr']['size'] = max(1, $options['widget_config']['height']);
        }
        $view->vars['attr'] = array_merge($view->vars['attr'], $options['widget_config']['jsevents']);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
