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
use midcom\datamanager\extension\transformer\imageTransformer;
use midcom\datamanager\validation\image as constraint;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Experimental image type
 */
class imageType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'error_bubbling' => false
        ]);
        helper::add_normalizers($resolver, [
            'widget_config' => [
                'map_action_elements' => false,
                'show_title' => true
            ],
            'type_config' => [
                'do_not_save_archival' => true,
                'derived_images' => [],
                'filter_chain' => null
            ]
        ]);

        $resolver->setNormalizer('constraints', function (Options $options, $value) {
            $constraint = new constraint([
                'required' => $options['required'],
                'config' => $options['type_config']
            ]);
            return [$constraint];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(new imageTransformer($options));
        $builder->add('file', FileType::class, ['required' => false]);
        if ($options['widget_config']['show_title']) {
            $builder->add('title', textType::class, ['required' => false]);
        }
        $builder->add('delete', CheckboxType::class, ['attr' => [
            "class" => "midcom_datamanager_photo_checkbox"
        ], "required" => false ]);
        $builder->add('identifier', HiddenType::class);

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
