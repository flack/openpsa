<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Form\AbstractType;
use midcom\datamanager\extension\helper;
use midcom;
use midcom\datamanager\extension\transformer\photo as transformer;
use midcom\datamanager\validation\photo as constraint;
use midcom\datamanager\extension\compat;

/**
 * Experimental image type
 */
class image extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'error_bubbling' => false
        ]);
        $resolver->setNormalizer('widget_config', function (Options $options, $value) {
            $widget_defaults = [
                'map_action_elements' => false,
                'show_title' => true
            ];
            return helper::resolve_options($widget_defaults, $value);
        });
        $resolver->setNormalizer('type_config', function (Options $options, $value) {
            $type_defaults = [
                'derived_images' => [],
                'filter_chain' => null
            ];
            return helper::resolve_options($type_defaults, $value);
        });
        $resolver->setNormalizer('constraints', function (Options $options, $value) {
            if ($options['required']) {
                return [new constraint()];
            }
            return [];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(new transformer($options));
        $builder->add('file', compat::get_type_name('file'), ['required' => false]);
        if ($options['widget_config']['show_title']) {
            $builder->add('title', compat::get_type_name('text'));
        }
        $builder->add('delete', compat::get_type_name('checkbox'), ['attr' => [
            "class" => "midcom_datamanager_photo_checkbox"
        ], "required" => false ]);
        $builder->add('identifier', compat::get_type_name('hidden'), ['data' => 'file']);

        $head = midcom::get()->head;
        $head->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.datamanager/image.css');
        $head->enable_jquery();
        $head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.datamanager/image.js');
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'image';
    }
}
