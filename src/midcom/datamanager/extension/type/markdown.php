<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\extension\type;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use midcom;
use midcom\datamanager\extension\compat;

/**
 * Experimental markdown type
 */
class markdown extends TextareaType
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

        $resolver->setDefaults([
            'attr' => $map_attr,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

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
        return compat::get_type_name('textarea');
    }
}
