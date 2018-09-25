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
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use midcom\datamanager\extension\helper;
use midcom\datamanager\validation\php;

/**
 * Experimental codemirror type
 */
class codemirrorType extends TextareaType
{
    /**
     * Widget version
     */
    public $version = '5.29.0';

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
            $value['class'] = $options['widget_config']['enabled'] ? 'codemirror ' . $options['widget_config']['language'] : 'longtext';
            return $value;
        };

        $get_config = function (Options $options, $value) {
            return \midcom_baseclasses_components_configuration::get('midcom.datamanager', 'config');
        };

        $resolver->setDefaults([
            'attr' => $map_attr,
            'config' => $get_config
        ]);

        $resolver->setNormalizer('widget_config', function (Options $options, $value) {
            $widget_defaults = [
                'enabled' => true,
                'language' => 'php',
            ];
            return helper::resolve_options($widget_defaults, $value);
        });
        $resolver->setNormalizer('type_config', function (Options $options, $value) {
            $type_defaults = [
                'modes' => ['xml', 'javascript', 'css', 'clike', 'php'],
            ];
            return helper::resolve_options($type_defaults, $value);
        });
        $resolver->setNormalizer('constraints', function (Options $options, $value) {
            if ($options['dm2_type'] == 'php') {
                $value[] = new php;
            }
            return $value;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        if ($options['widget_config']['enabled']) {
            $prefix = MIDCOM_STATIC_URL . '/midcom.datamanager/codemirror-' . $this->version;
            midcom::get()->head->enable_jquery();
            midcom::get()->head->add_stylesheet($prefix . '/lib/codemirror.css');
            midcom::get()->head->add_stylesheet($prefix . '/theme/eclipse.css');
            midcom::get()->head->add_jsfile($prefix . '/lib/codemirror.js');
            foreach ($options['type_config']['modes'] as $mode) {
                midcom::get()->head->add_jsfile($prefix . '/mode/' . $mode . '/' . $mode . '.js');
            }
            midcom::get()->head->add_jsfile($prefix . '/addon/edit/matchbrackets.js');
            midcom::get()->head->add_jsfile($prefix . '/addon/dialog/dialog.js');
            midcom::get()->head->add_stylesheet($prefix . '/addon/dialog/dialog.css');
            midcom::get()->head->add_jsfile($prefix . '/addon/search/searchcursor.js');
            midcom::get()->head->add_jsfile($prefix . '/addon/search/match-highlighter.js');
            midcom::get()->head->add_jsfile($prefix . '/addon/search/search.js');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        if ($options['widget_config']['enabled']) {
            $view->vars['codemirror_snippet'] = \midcom_helper_misc::get_snippet_content_graceful($options['config']->get('codemirror_config_snippet'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'codemirror';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TextareaType::class;
    }
}
