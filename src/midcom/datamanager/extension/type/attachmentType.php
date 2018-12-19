<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use midcom\datamanager\extension\transformer\attachmentTransformer;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use midcom;
use Symfony\Component\OptionsResolver\OptionsResolver;
use midcom\datamanager\extension\helper;
use Symfony\Component\Validator\Constraints\File;

/**
 * Attachment type.
 *
 * Contrary to what the name suggests, this handles one single attachment
 */
class attachmentType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        helper::add_normalizers($resolver, [
            'widget_config' => [
                'map_action_elements' => false,
                'show_title' => true,
                'show_description' => false,
                'sortable' => false
            ]
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('file', FileType::class, ['constraints' => [new File()], 'required' => false]);
        if ($options['widget_config']['show_title']) {
            $builder->add('title', textType::class);
        }
        if ($options['widget_config']['show_description']) {
            $builder->add('description', textType::class);
        }
        $builder->add('identifier', HiddenType::class);
        if ($options['widget_config']['sortable']) {
            $builder->add('score', HiddenType::class);
        }
        $builder->addViewTransformer(new attachmentTransformer($options));

        $head = midcom::get()->head;
        $head->add_stylesheet(MIDCOM_STATIC_URL . "/stock-icons/font-awesome-4.7.0/css/font-awesome.min.css");
        $head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.datamanager/attachment.js');
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'attachment';
    }
}
