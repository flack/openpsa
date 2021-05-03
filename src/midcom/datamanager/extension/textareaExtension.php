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
use Symfony\Component\Form\AbstractTypeExtension;

/**
 * Textarea extension
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
            $value['rows'] = $options['widget_config']['height'];
            $value['cols'] = $options['widget_config']['width'];

            return $value;
        };
        $resolver->setDefault('attr', $map_attr);

        helper::add_normalizers($resolver, [
            'widget_config' => [
                'height' => 6,
                'width' => 50
            ],
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

    public static function getExtendedTypes() : iterable
    {
        return [TextareaType::class];
    }
}
