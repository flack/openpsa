<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use midcom;
use Symfony\Component\Form\AbstractType;

/**
 * Experimental markdown type
 */
class markdownType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $head = midcom::get()->head;
        $head->add_stylesheet(MIDCOM_STATIC_URL . '/stock-icons/font-awesome-4.7.0/css/font-awesome.min.css');
        $head->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.datamanager/simplemde/simplemde.min.css');
        $head->enable_jquery();
        $head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.datamanager/simplemde/simplemde.min.js');
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'markdown';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TextareaType::class;
    }
}
