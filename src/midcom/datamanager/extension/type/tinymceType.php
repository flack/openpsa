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
use Symfony\Component\Form\AbstractType;

/**
 * tinyMCE type
 */
class tinymceType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('config', function (Options $options) {
            return \midcom_baseclasses_components_configuration::get('midcom.datamanager', 'config');
        });

        $resolver->setNormalizer('widget_config', function (Options $options, $value) {
            $widget_defaults = [
                'mode' => 'exact',
                'theme' => $options['config']->get('tinymce_default_theme'),
                'local_config' => '',
                'use_imagepopup' => true,
                'mce_config_snippet' => null
            ];
            return helper::normalize($widget_defaults, $value);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        midcom::get()->head->enable_jquery();
        midcom::get()->head->add_jsfile($options['config']->get('tinymce_url') . '/tinymce.min.js');
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.datamanager/tinymce.custom.js');
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $config = $this->get_configuration($options);
        $tiny_options = [
            'config' => $config,
            'mode' => $options['widget_config']['mode'],
            'elements' => $view->vars['id'],
            'local_config' => $options['widget_config']['local_config'],
            'language' => midcom::get()->i18n->get_current_language(),
            'img' => ($options['widget_config']['use_imagepopup']) ? $this->get_image_popup($form) : '',
        ];
        $view->vars['tinymce_snippet'] = $this->get_snippet($tiny_options);
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
        return TextareaType::class;
    }

    /**
     *
     * @param array $options
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

    private function get_snippet(array $tiny_configuration)
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
    private function get_image_popup(FormInterface $form)
    {
        $hostname = midcom::get()->get_host_name();
        $prefix = \midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
        $upload_url = $prefix . '__ais/imagepopup/upload/image/';
        $suffix = '';
        $url = $prefix . '__ais/imagepopup/open/';

        $data = $form->getParent()->getData();
        if ($object = $data->get_value()) {
            $suffix = $object->guid . '/';
            $upload_url .= $suffix;
        }

        $title = midcom::get()->i18n->get_l10n('midcom.helper.imagepopup')->get('file picker');
        $img = <<<IMG
file_picker_callback: tiny.filepicker('$title', '$url', '$suffix'),
imagetools_cors_hosts: ['{$hostname}'],
images_upload_handler: tiny.image_upload_handler('$upload_url'),
IMG;
        return $img;
    }
}
