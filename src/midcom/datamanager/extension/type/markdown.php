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
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use midcom\datamanager\extension\helper;

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

        $resolver->setNormalizer('type_config', function (Options $options, $value) {
            $type_defaults = [
                'output_mode' => 'html',
                'specialchars_quotes' => ENT_QUOTES,
                'specialchars_charset' => 'UTF-8'
            ];
            return helper::resolve_options($type_defaults, $value);
        });
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

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['output_mode'] = $options['type_config']['output_mode'];
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
