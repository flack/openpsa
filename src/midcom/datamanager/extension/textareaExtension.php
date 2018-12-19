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

/**
 * Textarea extension
 */
class textareaExtension extends textExtension
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $map_attr = function (Options $options, $value) {
            if ($value === null) {
                $value = [];
            }
            $value['rows'] = !empty($options['widget_config']['height']) ? $options['widget_config']['height'] : 6;
            $value['cols'] = !empty($options['widget_config']['width']) ? $options['widget_config']['width'] : 50;

            return $value;
        };
        $resolver->setDefault('attr', $map_attr);

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
