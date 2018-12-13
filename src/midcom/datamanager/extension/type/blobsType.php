<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use midcom\datamanager\extension\transformer\blobTransformer;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use midcom;

/**
 * Attachment type.
 *
 * Contrary to what the name suggests, this handles one single attachment
 */
class blobsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('title', textType::class);
        $builder->add('file', FileType::class, ['required' => false]);
        $builder->add('identifier', HiddenType::class);
        $builder->addViewTransformer(new blobTransformer($options));
        midcom::get()->head->add_stylesheet(MIDCOM_STATIC_URL . "/stock-icons/font-awesome-4.7.0/css/font-awesome.min.css");
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'blobs';
    }
}
