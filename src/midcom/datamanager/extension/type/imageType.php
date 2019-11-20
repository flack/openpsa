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
use midcom\datamanager\extension\transformer\imageTransformer;
use midcom\datamanager\validation\image as constraint;
use Symfony\Component\Validator\Constraints\Image as sf_constraint;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

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
        $resolver->setDefault('error_bubbling', false);

        helper::add_normalizers($resolver, [
            'widget_config' => [
                'map_action_elements' => false,
                'show_title' => true,
                'show_description' => false,
                'sortable' => false
            ],
            'type_config' => [
                'do_not_save_archival' => true,
                'derived_images' => [],
                'filter_chain' => null
            ]
        ]);

        $resolver->setNormalizer('constraints', function (Options $options, $value) {
            if ($options['required']) {
                return [new constraint(['config' => $options['type_config']])];
            }
            return [];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addViewTransformer(new imageTransformer($options));
        $builder->add('file', FileType::class, [
            'required' => false,
            'constraints' => [new sf_constraint],
            'attr' => ['accept' => 'image/*']
        ]);
        if ($options['widget_config']['show_title']) {
            $builder->add('title', TextType::class, ['required' => false]);
        }
        if ($options['widget_config']['show_description']) {
            $builder->add('description', TextType::class, ['required' => false]);
        }

        if ($options['widget_config']['sortable']) {
            $builder->add('score', HiddenType::class);
        }

        $builder->add('delete', CheckboxType::class, ['attr' => [
            "class" => "midcom_datamanager_photo_checkbox"
        ], "required" => false ]);
        $builder->add('identifier', HiddenType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'image';
    }
}
