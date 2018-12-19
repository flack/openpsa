<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use midcom\datamanager\validation\pattern as validator;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\FormBuilderInterface;
use midcom\datamanager\extension\subscriber\purifySubscriber;
use Symfony\Component\Form\AbstractTypeExtension;

/**
 * Experimental textarea type
 */
class textareaExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $map_attr = function (Options $options, $value) {
            if ($value === null) {
                $value = [];
            }
            $value['rows'] = !empty($options['widget_config']['height']) ? $options['widget_config']['height'] : 6;
            $value['cols'] = !empty($options['widget_config']['width']) ? $options['widget_config']['width'] : 50;

            return $value;
        };

        $resolver->setDefaults([
            'constraints' => [],
            'attr' => $map_attr
        ]);
        helper::add_normalizers($resolver, [
            'type_config' => [
                'output_mode' => 'html',
                'specialchars_quotes' => ENT_QUOTES,
                'specialchars_charset' => 'UTF-8',
                'forbidden_patterns' => [],
                'maxlength' => 0,
                'purify' => false,
                'purify_config' => []
            ]
        ]);
        $resolver->setNormalizer('attr', function (Options $options, $value) {
            if (!empty($options['widget_config']['height'])) {
                $value['rows'] = $options['widget_config']['height'];
            }
            return $value;
        });
        $resolver->setNormalizer('constraints', function (Options $options, $value) {
            if (!empty($options['type_config']['forbidden_patterns'])) {
                $value[] = new validator(['forbidden_patterns' => $options['type_config']['forbidden_patterns']]);
            }
            if (!empty($options['type_config']['maxlength'])) {
                $value[] = new Length(['max' => $options['type_config']['maxlength']]);
            }
            return $value;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!empty($options['type_config']['purify'])) {
            $builder->addEventSubscriber(new purifySubscriber($options['type_config']['purify_config']));
        }
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['output_mode'] = $options['type_config']['output_mode'];
    }

    // Symfony < 4.2 compat
    public function getExtendedType()
    {
        return TextareaType::class;
    }

    public static function getExtendedTypes()
    {
        return [TextareaType::class];
    }
}
