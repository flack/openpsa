<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use midcom\datamanager\extension\transformer\multiple;
use Symfony\Component\Form\FormBuilderInterface;
use midcom\datamanager\extension\helper;

/**
 * Experimental select type
 */
class radiocheckselect extends ChoiceType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $map_options = function (Options $options) {
            $return_options = [];
            if (!empty($options['type_config']['options'])) {
                foreach ($options['type_config']['options'] as $key => $value) {
                    //symfony expects only strings
                    $return_options[(string)$value] = (string)$key;
                }
            } elseif (isset($options['type_config']['option_callback'])) {
                $classname = $options['type_config']['option_callback'];
                $callback = new $classname($options['type_config']['option_callback_arg']);
                foreach ($callback->list_all() as $key => $value) {
                    //symfony expects only strings
                    $return_options[(string)$value] = (string)$key;
                }
            }
            return $return_options;
        };
        $map_multiple = function (Options $options) {
            return !empty($options['type_config']['allow_multiple']);
        };

        $resolver->setDefaults([
            'choices' => $map_options,
            'expanded' => true,
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
            return helper::resolve_options($type_defaults, $value);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['multiple']) {
            $builder->addModelTransformer(new multiple($options));
        }
        parent::buildForm($builder, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $view->vars['attr']['class'] = $options['multiple'] ? 'checkbox' : 'radiobox';
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'radiocheckselect';
    }
}
