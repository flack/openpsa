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
 * As with all subclasses, the actual initialization is done in the initialize() function,
 * not in the constructor, to allow for error handling.
 *
 * This widget supports all types which have a simple, plain text representation accessible
 * though their <i>value</i> member. The class will put HTML into those base types.
 *
 * This type extends the regular textarea type, as this is the fallback for all cases where
 * TinyMCE doesn't run (on Opera f.x.).
 *
 * <b>Available configuration options:</b>
 *
 * - All of the textarea baseclass. The height default has been changed to 25, the width default
 *   to 80.
 * - <i>string mce_config_snippet:</i> Indicates the name of the snippet which holds the base
 *   configuration. This is looked up in the DM2 directory in SG-Config. This defaults to
 *   midcom::get('config')->get('midcom_sgconfig_basedir') . '/midcom.helper.datamanager2/tinymce'.
 *   Any valid option for midcom_helper_misc::get_snippet_content() is allowed at this point.
 * - <i>string local_config:</i> Local configuration options which should overwrite the defaults
 *   from the config snippet. This defaults to an empty string.
 * - <i>boolean tinymce_use_compressor:</i> TinyMCE's PHP Compressor can help to reduce the page
 *   load time. Defaults to false.
 * - <i>theme</i> use this to change between a simple and an advanced (i.e. more buttons)
 *   configuration of tinymce. Valid values: simple, advanced and tiny. The systemwide default
 *   for this value can be set in the tinymce_default_theme DM2 configuration option.
 * - <i>boolean use_imagepopup</i> Defaults to yes. Use the imagepopup it the element is attached
 *   to an object.
 *
 * - <i>string imagepopup_object</i> If you want to override the normal object (f.x. to have a
 *   central attachments object). Set that object's guid here. NOTE: NOT IMPLEMENTED YET. MAY CHANGE!
 *
 * <b>Notes about TinyMCE configuration:</b>
 *
 * TinyMCE uses a JScript array, outlined in http://tinymce.moxiecode.com/tinymce/docs/reference_configuration.html
 * to configure itself. A different configuration can be used for each textarea, as all of them
 * are initialized individually. If the specified snippet is not used, some default configuration
 * is used (see the private function _get_advanced_configuration).
 *
 * Configuration is specified in an already JScript compatible way: The main config snippet is included
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
    var $mce_config_snippet = null;

    /**
     * Local configuration to be added to the config snippet.
     *
     * @var string
     */
    var $local_config = '';

    /**
     * Define some simple configuration themes without having to create a config file.
     *
     * valid values: simple, advanced or tiny
     *
     * @var string
     */
    var $theme = null;

    /**
     * Should the imagepopup button be shown?
     *
     * @var boolean defaults to true.
     */
    var $use_imagepopup = true;

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
        if ($this->_config->get('tinymce_use_compressor'))
        {
            midcom::get('head')->add_jsfile("{$prefix}/tiny_mce_gzip.js", true);
        }
        else
        {
            midcom::get('head')->add_jsfile("{$prefix}/tiny_mce.js", true);
        }
    }

    /**
     * This helper will construct the TinyMCE initscript based on the specified configuration.
     */
    function _add_initscript($mode = 'exact')
    {
        $config = midcom_helper_misc::get_snippet_content_graceful($this->mce_config_snippet);

        if (! $config)
        {
            $config = $this->_get_configuration();
        }
        else
        {
            $popup = $this->_get_imagepopup_jsstring();
            $config = str_replace('{$popup}', $popup, $config);
        }

        $language = midcom::get('i18n')->get_current_language();
        // fix to use the correct langcode for norwegian.
        if ($language == 'no')
        {
             $language = 'nb';
        }

        $imagepopup_url = '';
        if ($this->use_imagepopup)
        {
            $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
            $imagepopup_url = "plugin_imagepopup_popupurl: \"{$prefix}__ais/imagepopup/";

            if ($this->_type->storage->object)
            {
                // We have an existing object, link to "page attachments"
                $imagepopup_url .= "{$this->_schema->name}/{$this->_type->storage->object->guid}\",";
            }
            else
            {
                // No object has been created yet, link to "folder attachments" without page specified
                $imagepopup_url .= "folder/{$this->_schema->name}\",";
            }
        }

        if ($this->_config->get('tinymce_use_compressor'))
        {
            $gz_config = preg_replace("/^theme\s*?:/", "themes :", $config);
            $script_gz = <<<EOT
tinyMCE_GZ.init({
{$gz_config}
{$this->local_config}
languages : "{$language}",
{$imagepopup_url}
disk_cache : true,
debug : false
});
EOT;
            midcom::get('head')->add_jscript($script_gz);
        }

        // Compute the final script:
        $script = <<<EOT
tinyMCE.init({
{$config}
{$this->local_config}
mode : "{$mode}",
convert_urls : false,
relative_urls : false,
remove_script_host : true,
elements : "{$this->_namespace}{$this->name}",
language : "{$language}",
{$imagepopup_url}
docs_language : "{$language}",
browsers : "msie,gecko,opera,safari"
});
EOT;

        midcom::get('head')->add_jscript($script);
    }

    /**
     * Returns the string ,imagepopup that is added if we are editing a
     * saved object (and thus can add attachments)
     *
     * @return string empty or containing ",imagepopup"
     */
    function _get_imagepopup_jsstring()
    {
        if ($this->_type->storage !== null)
        {
            return ",imagepopup";
        }
        return "";
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
     * Returns the default/simple configuration:
     *
     * <pre>
     * theme : "advanced",
     * button_title_map : false,
     * apply_source_formatting : true,
     * plugins : "table,contextmenu,paste,fullscreen",
     * theme_advanced_buttons2_add : "separator,fullscreen,selectall,pastetext,pasteword,",
     * theme_advanced_buttons3_add : "separator,tablecontrols",
     * paste_create_linebreaks : false,
     * </pre>
     *
     * @return string The default configuration
     */
    private function _get_simple_configuration()
    {
        $popup = $this->_get_imagepopup_jsstring();
        return <<<EOT
theme : "advanced",
button_title_map : false,
apply_source_formatting : true,
plugins : "table,contextmenu,advimage,advlink,paste,fullscreen$popup",
theme_advanced_buttons1 : "cut,copy,paste,separator,undo,redo,separator,justifyleft,justifycenter,justifyright,separator,outdent,indent,separator,code,fullscreen",
theme_advanced_buttons2 : "formatselect,separator,bold,italic,separator,bullist,numlist,separator,link,imagepopup",
theme_advanced_buttons3 : "",
theme_advanced_toolbar_align : "left",
theme_advanced_toolbar_location : "top",
paste_create_linebreaks : false,
EOT;
    }

    /**
     * Returns the "advanced" configuration
     */
    private function _get_advanced_configuration ()
    {
        $popup = $this->_get_imagepopup_jsstring();
        return <<<EOT
apply_source_formatting : true,
theme : "advanced",
plugins : "table,save,advhr,advimage,advlink,iespell,insertdatetime,preview,zoom,flash,searchreplace,print,contextmenu,fullscreen{$popup}",
theme_advanced_buttons1 : "cut,copy,paste,separator,undo,redo,separator,replace,separator,justifyleft,justifycenter,justifyright,separator,outdent,indent",
theme_advanced_buttons2 : "formatselect,separator,bold,italic,strikethrough,sub,sup,separator,bullist,numlist,separator,imagepopup",
theme_advanced_buttons3 : "tablecontrols,separator,cleanup,code,removeformat,visualaid,iespell,separator,fullscreen",
theme_advanced_toolbar_location : "top",
theme_advanced_toolbar_align : "left",
theme_advanced_path_location : "bottom",
plugin_insertdate_dateFormat : "%Y-%m-%d",
plugin_insertdate_timeFormat : "%H:%M:%S",
extended_valid_elements : "a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name|style],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]",

EOT;
    }

    /**
     * Returns the "tiny" configuration
     */
    private function _get_tiny_configuration ()
    {
        $popup = $this->_get_imagepopup_jsstring();
        return <<<EOT
apply_source_formatting : true,
theme : "advanced",
plugins : "table,save,advimage,advlink,zoom,contextmenu,fullscreen{$popup}",
theme_advanced_buttons1 : "bold,italic,separator,bullist,separator,link,imagepopup,separator,code,fullscreen",
theme_advanced_buttons2 : "",
theme_advanced_buttons3 : "",
theme_advanced_toolbar_location : "top",
theme_advanced_toolbar_align : "left",
extended_valid_elements : "a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name|style],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]",

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

        if ($this->use_imagepopup)
        {
            if ($this->_type->storage->object)
            {
                // We have an object, register the schema using that object
                $this->_schema->register_to_session($this->_type->storage->object->guid);
            }
            else
            {
                // No object has been created yet. Therefore, we register the schema for the topic GUID
                $topic = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_CONTENTTOPIC);
                $this->_schema->register_to_session($topic->guid);
            }
        }
    }

    /**
     * This is called during intialization the function is used to
     * register the schema to a session key
     *
     * @return boolean always true
     */
     public function _on_initialize()
     {
        if ($this->_initialize_dependencies)
        {
            $this->_add_external_html_elements();
            $this->_add_initscript('none');
        }
        return true;
     }

    /**
     * Adds a simple single-line text form element at this time.
     *
     * Note, that this is a copy of the base class function, as we need another CSS rule here
     * besides the additional initialization code.
     */
    function add_elements_to_form($attributes)
    {
        if (!$this->_initialize_dependencies)
        {
            $this->_add_external_html_elements();
            $this->_add_initscript();
        }

        $attributes = array_merge($attributes, array
        (
            'rows' => $this->height,
            'cols' => $this->width,
            'class' => 'tinymce',
            'id'    => "{$this->_namespace}{$this->name}",
        ));
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
    function freeze()
    {
        $original_element = $this->_form->getElement($this->name);

        foreach ($this->_form->_elements as $key => $element)
        {
            if ((isset($element->_attributes['name']))
                && ($element->_attributes['name'] == $this->name))
            {
                if (isset($this->_form->_elements[$key + 1]->_attributes['name']))
                {
                    $name_after = $this->_form->_elements[$key + 1]->_attributes['name'];
                }
            }
        }

        $this->_form->removeElement($this->name);

        $new_element = new HTML_QuickForm_hidden($original_element->getName(), $original_element->getLabel());
        $new_element->setValue($original_element->getValue());

        $value_container = HTML_QuickForm::createElement('static', $original_element->getName() . '_previews', $original_element->getLabel(), $original_element->getValue());

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
?>
