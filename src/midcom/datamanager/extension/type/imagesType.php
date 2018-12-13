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
use midcom\datamanager\extension\transformer\blobTransformer;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

/**
 * Images type
 *
 * Contrary to what the name suggests, this handles one single image
 */
class imagesType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setNormalizer('widget_config', function (Options $options, $value) {
            $widget_defaults = [
                'map_action_elements' => false,
                'show_title' => true,
                'show_description' => false,
                'sortable' => false
            ];
            return helper::resolve_options($widget_defaults, $value);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('file', FileType::class);
        if ($options['widget_config']['show_title']) {
            $builder->add('title', textType::class);
        }
        if ($options['widget_config']['show_description']) {
            $builder->add('description', textType::class);
        }
        $builder->add('identifier', HiddenType::class, ['data' => 'file']);
        if ($options['widget_config']['sortable']) {
            $builder->add('score', HiddenType::class);
        }
        $builder->addViewTransformer(new blobTransformer($options));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'images';
    }
}
