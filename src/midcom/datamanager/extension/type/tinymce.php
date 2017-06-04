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
use midcom_helper_misc;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use midcom\datamanager\extension\helper;
use midcom\datamanager\extension\compat;

/**
 * Experimental textarea type
 */
class tinymce extends TextareaType
{
    /**
     * Widget version
     */
    public $version = '4.3';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $map_attr = function (Options $options, $value) {
            if ($value === null) {
                $value = array();
            }
            $value['rows'] = !empty($options['widget_config']['height']) ? $options['widget_config']['height'] : 6;
            $value['cols'] = !empty($options['widget_config']['width']) ? $options['widget_config']['width'] : 50;

            return $value;
        };

        $get_config = function (Options $options, $value) {
            return \midcom_baseclasses_components_configuration::get('midcom.helper.datamanager2', 'config');
        };

        $resolver->setDefaults(array(
            'attr' => $map_attr,
            'config' => $get_config
        ));

        $resolver->setNormalizer('widget_config', function (Options $options, $value) {
            $widget_defaults = array(
                'mode' => 'exact',
                'theme' => $options['config']->get('tinymce_default_theme'),
                'local_config' => '',
                'use_imagepopup' => true,
                'mce_config_snippet' => null
            );
            return helper::resolve_options($widget_defaults, $value);
        });
        $resolver->setNormalizer('type_config', function (Options $options, $value) {
            $type_defaults = array(

            );
            return helper::resolve_options($type_defaults, $value);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        midcom::get()->head->enable_jquery();
        midcom::get()->head->add_jsfile($options['config']->get('tinymce_url') . '/tinymce.min.js');
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $config = $this->get_configuration($options);
        $tiny_options = array(
            'config' => $config,
            'mode' => $options['widget_config']['mode'],
            'elements' => $view->vars['id'],
            'local_config' => $options['widget_config']['local_config'],
            'language' => midcom::get()->i18n->get_current_language(),
            'img' => ($options['widget_config']['use_imagepopup'])? $this->_get_image_popup($form) : '',
        );
        $snippet = $this->_get_snippet($tiny_options);
        $view->vars['tinymce_snippet'] = $snippet;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'tinymce';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return compat::get_type_name('textarea');
    }

    /**
     *
     * @param string $name
     */
    private function get_configuration(array $options)
    {
        if (!empty($options['widget_config']['mce_config_snippet'])) {
            $config = midcom_helper_misc::get_snippet_content_graceful($options['widget_config']['mce_config_snippet']);
        }
        if (empty($config)) {
            $config = midcom_helper_misc::get_snippet_content_graceful($options['config']->get('tinymce_default_config_snippet'));
        }
        return $config;
    }

    private function _get_snippet($tiny_configuration)
    {
        $config = $tiny_configuration['config'];
        $local_config = $tiny_configuration['local_config'];
        $mode = $tiny_configuration['mode'];
        $elements = $tiny_configuration['elements'];
        $language = $tiny_configuration['language'];
        $img = $tiny_configuration['img'];

        $script = <<<EOT
tinyMCE.init({
{$config}
{$local_config}
mode : "{$mode}",
convert_urls : false,
relative_urls : false,
remove_script_host : true,
elements : "{$elements}",
language : "{$language}",
{$img}
});
EOT;
        return $script;
    }

    /**
     * Build image popup with schema name & object
     *
     * @param FormInterface $form
     */
    private function _get_image_popup(FormInterface $form)
    {
        $prefix = \midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
        $suffix = '';
        $imagepopup_url = $prefix . '__ais/imagepopup/open/';

        $data = $form->getParent()->getData();
        if ($object = $data->get_value()) {
            $suffix = $object->guid . '/';
        }

        $title = midcom::get()->i18n->get_l10n()->get('file picker');
        $img = <<<IMG
file_picker_callback: function(callback, value, meta) {
    tinymce.activeEditor.windowManager.open({
        title: "{$title}",
        url: "{$imagepopup_url}" + meta.filetype + '/' + "{$suffix}",
        width: 800,
        height: 600
    }, {
        oninsert: function(url, meta) {
            callback(url, meta);
        }
    });
},
IMG;
        return $img;
    }
}
