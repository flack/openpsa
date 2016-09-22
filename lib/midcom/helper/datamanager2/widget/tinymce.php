<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 TinyMCE driven textarea widget
 *
 * As with all subclasses, the actual initialization is done in the initialize() function.
 *
 * This widget supports all types which have a simple, plain text representation accessible
 * though their <i>value</i> member. The class will put HTML into those base types.
 *
 * This type extends the regular textarea type, as this is the fallback for all cases where
 * TinyMCE doesn't run.
 *
 * <b>Available configuration options:</b>
 *
 * - All of the textarea baseclass. The height default has been changed to 25, the width default
 *   to 80.
 * - <i>string mce_config_snippet:</i> Indicates the name of the snippet which holds the base
 *   configuration. This is looked up in the DM2 directory in SG-Config. This defaults to
 *   midcom::get()->config->get('midcom_sgconfig_basedir') . '/midcom.helper.datamanager2/tinymce'.
 *   Any valid option for midcom_helper_misc::get_snippet_content() is allowed at this point.
 * - <i>string local_config:</i> Local configuration options which should overwrite the defaults
 *   from the config snippet. This defaults to an empty string.
 * - <i>theme</i> use this to change between a simple and an advanced (i.e. more buttons)
 *   configuration of tinymce. Valid values: simple, advanced and tiny. The systemwide default
 *   for this value can be set in the tinymce_default_theme DM2 configuration option.
 * - <i>boolean use_imagepopup</i> Defaults to yes. Use the imagepopup it the element is attached
 *   to an object.
 *
 * <b>Notes about TinyMCE configuration:</b>
 *
 * TinyMCE uses a JavaScript array, outlined in http://tinymce.moxiecode.com/tinymce/docs/reference_configuration.html
 * to configure itself. A different configuration can be used for each textarea, as all of them
 * are initialized individually. If the specified snippet is not used, some default configuration
 * is used (see the private function _get_advanced_configuration).
 *
 * Configuration is specified in an already JavaScript compatible way: The main config snippet is included
 * verbatim, as is the information in the local_config option.
 *
 * The following options must not be specified in any configuration: mode, elements, language
 *
 * Be aware that this must be valid javascript code to be inserted into the Init function. Especially
 * ensure that <i>all lines end with a comma</i> or the merging with the element-specific startup
 * code will fail. This is important for both the config-snippet and the local config!
 *
 * Example:
 *
 * If you add this as the configuration snippet:
 *
 * <pre>
 * theme : "advanced",
 * cleanup: false,
 * </pre>
 *
 * ... we will get this startup code:
 *
 * <pre>
 * tinyMCE.init({
 *   theme : "advanced",
 *   cleanup: false,
 *   mode: "textarea",
 *   ...
 * });
 * </pre>
 *
 * In case you have anything in local config, it will be added below the configuration snippet and
 * above the element specific startup code. (Which is important if you specify the same key twice.)
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_tinymce extends midcom_helper_datamanager2_widget_textarea
{
    /**
     * The MCE configuration snippet to use. Argument must be applicable to use with
     * midcom_helper_misc::get_snippet_content.
     *
     * @var string
     */
    public $mce_config_snippet;

    /**
     * Local configuration to be added to the config snippet.
     *
     * @var string
     */
    public $local_config;

    /**
     * Define some simple configuration themes without having to create a config file.
     *
     * valid values: simple, advanced or tiny
     *
     * @var string
     */
    public $theme;

    /**
     * Should the imagepopup button be shown?
     *
     * @var boolean
     */
    public $use_imagepopup = true;

    /**
     * Adds the external HTML dependencies, both JS and CSS. A static flag prevents
     * multiple insertions of these dependencies.
     */
    private function _add_external_html_elements()
    {
        static $executed = false;

        if ($executed)
        {
            return;
        }

        $executed = true;

        $prefix = $this->_config->get('tinymce_url');
        midcom::get()->head->add_jsfile("{$prefix}/tinymce.min.js", true);
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . "/midcom.helper.datamanager2/imagetools_func.js", true);
    }

    /**
     * This helper will construct the TinyMCE initscript based on the specified configuration.
     */
    private function _add_initscript($mode = 'exact')
    {
        $config = midcom_helper_misc::get_snippet_content_graceful($this->mce_config_snippet);

        if (!$config)
        {
            $config = $this->_get_configuration();
        }

        $language = $this->_i18n->get_current_language();
        // fix to use the correct langcode for norwegian.
        if ($language == 'no')
        {
             $language = 'nb';
        }

        $img = '';
        if ($this->use_imagepopup)
        {
            $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
            $suffix = '';
            $imagepopup_url = $prefix . '__ais/imagepopup/open/' . $this->_schema->name . '/';

            if ($this->_type->storage->object)
            {
                $suffix = $this->_type->storage->object->guid . '/';
            }

            $title = $this->_l10n->get('file picker');
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
        }

        // Compute the final script:
        $script = <<<EOT
var original = "";
tinyMCE.init({
{$config}
{$this->local_config}
mode : "{$mode}",
convert_urls : false,
relative_urls : false,
remove_script_host : true,
elements : "{$this->_namespace}{$this->name}",
language : "{$language}",
{$this->_get_imagetools_configuration()}
{$img}
});
EOT;

        midcom::get()->head->add_jscript($script);
    }

    /**
     * Returns the configuration theme based on the local_config_theme.
     *
     * @return string
     */
    private function _get_configuration()
    {
        $ok_elements = array
        (
            'simple'   => true,
            'advanced' => true,
            'tiny'     => true,
        );
        if (array_key_exists($this->theme, $ok_elements))
        {
            $function = "_get_{$this->theme}_configuration";
            return $this->$function();
        }
        if ($this->mcs_config_snippet != '')
        {
            return $this->mcs_config_snippet;
        }
        
        return $this->_get_advanced_configuration();
    }

    /**
     * Returns the default/simple configuration
     *
     * @return string The default configuration
     */
    private function _get_simple_configuration()
    {
        return <<<EOT
theme: "modern",
menubar: false,
plugins: ["table contextmenu paste link fullscreen image imagetools"],
toolbar1: "cut copy paste | undo redo | alignleft alignjustify alignright | outdent indent | code fullscreen",
toolbar2: "formatselect | bold italic | bullist numlist | link unlink | image table",
{$this->_get_imagetools_configuration()}
EOT;
    }

    /**
     * Returns the "advanced" configuration
     */
    private function _get_advanced_configuration ()
    {
        return <<<EOT
theme: "modern",
plugins: ["table save hr link insertdatetime preview searchreplace print contextmenu fullscreen image imagetools"],
toolbar1: "cut copy paste | undo redo | searchreplace | alignleft alignjustify alignright | outdent indent | code removeformat | fullscreen",
toolbar2: "formatselect | bold italic strikethrough subscript superscript | link unlink | bullist numlist | image",
extended_valid_elements : "a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name|style],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]",
image_advtab: true,
{$this->_get_imagetools_configuration()}
EOT;
    }

    /**
     * Returns the "tiny" configuration
     */
    private function _get_tiny_configuration ()
    {
        return <<<EOT
theme : "modern",
menubar: false,
statusbar: false,
plugins : ["table save contextmenu link fullscreen image imagetools"],
toolbar: "bold italic | bullist | link image | code fullscreen",
extended_valid_elements : "a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name|style],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]",
{$this->_get_imagetools_configuration()}
        
EOT;
    }

    /**
     * Returns the imagetools configuration
     */
    private function _get_imagetools_configuration()
    {
        $hostname = midcom::get()->get_host_name();
        $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
        $url = $prefix . '__ais/imagepopup/upload/image/';
        
        if(!empty($this->_type->storage->object))
        {
            $url .= $this->_type->storage->object->guid . '/';
        }
    
        return <<<EOT
imagetools_toolbar: "editimage imageoptions",
imagetools_cors_hosts: ['{$hostname}'],
setup: imagetools_functions.setup,
images_upload_handler: imagetools_functions.images_upload_handler('{$url}'),
EOT;
    }

    /**
     * This changes the defaults for the textarea size to something more usable for a
     * WYSIWYG editor.
     *
     * The systemwide defaults for the theme and the mce config snippet will be loaded
     * from the config file at this point.
     *
     * @todo make overrideable.
     */
    public function _on_configuring()
    {
        parent::_on_configuring();

        $this->theme = $this->_config->get('tinymce_default_theme');
        $this->mce_config_snippet = $this->_config->get('tinymce_default_config_snippet');

        $this->height = 25;
        $this->width = 80;
    }

    /**
     * This is called during initialization the function is used to
     * register the schema to a session key
     */
    public function _on_initialize()
    {
        if ($this->_initialize_dependencies)
        {
            $this->_add_external_html_elements();
            $this->_add_initscript('none');
        }
    }

    /**
     * Adds a simple single-line text form element at this time.
     *
     * Note, that this is a copy of the base class function, as we need another CSS rule here
     * besides the additional initialization code.
     */
    public function add_elements_to_form($attributes)
    {
        if (!$this->_initialize_dependencies)
        {
            $this->_add_external_html_elements();
            $this->_add_initscript();
        }

        $attribute['rows'] = $this->height;
        $attributes['cols'] = $this->width;
        $attributes['class'] = 'tinymce';
        if ($this->wrap != '')
        {
            $attributes['wrap'] = $this->wrap;
        }

        $this->_form->addElement('textarea', $this->name, $this->_translate($this->_field['title']), $attributes);
        $this->_form->applyFilter($this->name, 'trim');

        if ($this->maxlength > 0)
        {
            $errormsg = sprintf($this->_l10n->get('type text: value is longer than %d characters'), $this->maxlength);
            $this->_form->addRule($this->name, $errormsg, 'maxlength', $this->maxlength);
        }
    }

    /**
     * Freeze the tinymce content by replacing textarea form element
     */
    public function freeze()
    {
        $original_element = $this->_form->getElement($this->name);

        foreach ($this->_form->_elements as $key => $element)
        {
            if (   isset($element->_attributes['name'])
                && $element->_attributes['name'] == $this->name
                && isset($this->_form->_elements[$key + 1]->_attributes['name']))
            {
                $name_after = $this->_form->_elements[$key + 1]->_attributes['name'];
                break;
            }
        }

        $this->_form->removeElement($this->name);

        $new_element = new HTML_QuickForm_hidden($original_element->getName(), $original_element->getLabel());
        $new_element->setValue($original_element->getValue());

        $value_container = $this->_form->createElement('static', $original_element->getName() . '_previews', $original_element->getLabel(), $original_element->getValue());

        if (isset($name_after))
        {
            $this->_form->insertElementBefore($new_element, $name_after);
            $this->_form->insertElementBefore($value_container, $name_after);
        }
        else
        {
            $this->_form->addElement($new_element);
            $this->_form->addElement($value_container);
        }
    }
}
