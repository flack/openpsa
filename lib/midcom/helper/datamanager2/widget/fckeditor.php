<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: fckeditor.php 22990 2009-07-23 15:46:03Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/** Base class */
require_once(MIDCOM_ROOT . '/midcom/helper/datamanager2/widget/textarea.php');

/**
 * Datamanager 2 FCKeditor driven textarea widget
 *
 * As with all subclasses, the actual initialization is done in the initialize() function,
 * not in the constructor, to allow for error handling.
 *
 * This widget supports all types which have a simple, plain text representation accessible
 * though their <i>value</i> member. The class will put HTML into those base types.
 *
 * This type extends the regular textarea type, as this is the fallback for all cases where
 * FCKeditor doesn't run (on Opera f.x.).
 *
 * <b>Available configuration options:</b>
 *
 * - All of the textarea baseclass. The height default has been changed to 450, the width default
 *   to 800.
 * - <i>string fck_config_snippet:</i> Indicates the name of the snippet which holds the base
 *   configuration. This is looked up in the DM2 directory in SG-Config. This defaults to
 *   '$GLOBALS['midcom_config']['midcom_sgconfig_basedir']/midcom.helper.datamanager2/fckeditor'.
 *   Any valid option for midcom_get_snippet_content() is allowed at this point.
 * - <i>string local_config:</i> Local configuration options which should overwrite the defaults
 *   from the config snippet. This defaults to an empty string.
 * - <i>theme</i> Valid values: default, office and silver. The systemwide default
 *   for this value can be set in the fck_default_theme DM2 configuration option.
 * - <i>boolean use_midcom_imagedialog</i> Use the midcom version of the image dialog. Defaults to yes.
 * - <i>toolbar_set</i> Valid predefined values: Default and Basic. The systemwide default
 *   for this value can be set in the fck_default_toolbar_set DM2 configuration option.
 *
 * <b>Notes about FCKeditor configuration:</b>
 *
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_fckeditor extends midcom_helper_datamanager2_widget_textarea
{
    /**
     * Width of the editor.
     *
     * @var int
     * @access public
     */
    var $width = null;

    /**
     * Height of the editor.
     *
     * @var int
     * @access public
     */
    var $height = null;

    /**
     * The FCK configuration snippet to use. Argument must be applicable to use with
     * midcom_get_snippet_content.
     *
     * @var string
     */
    var $fck_config_snippet = null;

    /**
     * Local configuration to be added to the config snippet.
     *
     * @var string
     */
    var $local_config = null;
    
    /**
     * Local js configuration to be added to the config snippet.
     *
     * @var string
     */
    var $local_js_config = null;

    /**
     * Which theme will the editor use to render itself
     * 
     * valid values: default, office2003 and silver
     *
     * @var string
     */
    var $theme = null;
    
    /**
     * Define some simple configuration without having to create a configs.
     * 
     * valid values: Default or Basic
     *
     * @var string
     */
    var $toolbar_set = null;
    var $toolbar_set_content = array();
    
    var $_static_toolbar_sets = array
    (
        'Default',
        'Basic',
    );
    var $_default_toolbar_set_contents = array();
    
    var $_editor;
    
    var $configuration = array();
    
    var $fckeditor_path = null;

    /**
     * This helper will construct the FCKeditor initscript based on the specified configuration.
     */
    function _add_initscript()
    {
        $this->_get_configuration();
        
        $language = $_MIDCOM->i18n->get_current_language();
        // fix to use the correct langcode for norwegian.
        if ($language == 'no')
        {
             $language = 'nb';
        }
    }
    
    /**
     * Returns the configuration theme based on the local_config_toolbar_set.
     * @return string
     * @access private
     */
    function _get_configuration()
    {
        $this->toolbar_set_contents = $this->_default_toolbar_set_contents;
        
        if (! empty($this->local_config))        
        {
            $this->configuration['fckconfig'] = $this->local_config;            
        }

        if (! empty($this->local_js_config))        
        {
            $this->configuration['js_config'] = $this->local_js_config;            
        }

        if (!is_null($this->toolbar_set))
        {
            $this->configuration['toolbar_set'] = $this->toolbar_set;
        }        
        
        $this->configuration['width'] = "800px";
        $this->configuration['height'] = "450px";
                
        if (! is_null($this->width))
        {
            if (is_numeric($this->width))
            {
                $this->width = "{$this->width}px";
            }
            $this->configuration['width'] = $this->width;
        }
        if (! is_null($this->height))
        {
            if (is_numeric($this->height))
            {
                $this->height = "{$this->height}px";
            }
            $this->configuration['height'] = $this->height;
        }
        
        if ($this->fck_config_snippet != '')
        {
            $data = midcom_get_snippet_content($this->fck_config_snippet);
            $result = eval ("\$snippet_config = Array ( {$data}\n );");
            if ($result === false)
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                    "Failed to parse the config file in '{$this->fck_config_snippet}', see above for PHP errors.");
                // This will exit.
            }

            if (isset($snippet_config['fckconfig']))
            {
                $this->configuration['fckconfig'] = $snippet_config['fckconfig'];
                if (! empty($this->local_config))
                {
                    $this->configuration['fckconfig'] = array_merge($this->configuration['fckconfig'], $this->local_config);            
                }
            }
            
            if (isset($snippet_config['js_config']))
            {
                $this->configuration['js_config'] = $snippet_config['js_config'];
                if (! empty($this->local_js_config))
                {
                    $this->configuration['js_config'] = array_merge($this->configuration['js_config'], $this->local_js_config);            
                }
            }
            
            if (   isset($snippet_config['toolbar_set'])
                && is_null($this->toolbar_set))
            {
                $this->configuration['toolbar_set'] = $snippet_config['toolbar_set'];
            }
            
            if (   isset($snippet_config['toolbar_set_content'])
                && !empty($snippet_config['toolbar_set_content']))
            {
                $this->toolbar_set_contents = array_merge($this->toolbar_set_content, $snippet_config['toolbar_set_content']);
            }           
            
            if (   isset($snippet_config['width'])
                && is_null($this->width))
            {
                $this->configuration['width'] = $snippet_config['width'];
            }
            
            if (   isset($snippet_config['height'])
                && is_null($this->height))
            {
                $this->configuration['height'] = $snippet_config['height'];
            }
        }
        
        if (! empty($this->toolbar_set_content))
        {
            $this->toolbar_set_contents = array_merge(
                $this->toolbar_set_contents,
                array
                (
                    $this->configuration['toolbar_set'] => $this->toolbar_set_content
                )
            );            
        }
        
        if (   !in_array($this->toolbar_set, $this->_static_toolbar_sets)
            && isset($this->toolbar_set_contents[$this->configuration['toolbar_set']]))
        {
            $this->configuration['toolbar_set_content'] = $this->toolbar_set_contents[$this->configuration['toolbar_set']];
        }
        
        if (empty($this->configuration['fckconfig']))
        {
            $this->configuration['fckconfig'] = array
            (
                'SkinPath' => 'editor/skins/office2003/',
                'DefaultLanguage' => 'en-uk',
                'UseBROnCarriageReturn' => 'false',
                'StartupFocus' => 'false',
            );
        }
        
        $this->configuration['fckconfig']['CustomConfigurationsPath'] = $this->_resolve_custom_config_path();
        
    }
    
    function _resolve_custom_config_path()
    {
        $prefix = $_MIDCOM->get_host_prefix();
        $config_str = '';
        
        $tmp_config = $this->configuration['js_config'];
        
        if (isset($this->configuration['toolbar_set_content']))
        {
            $tmp_config['toolbar_set'] = $this->configuration['toolbar_set'];
            $tmp_config['toolbar_content'] = $this->configuration['toolbar_set_content'];
        }
        
        //var_dump($tmp_config);
        
        $config_str = base64_encode(serialize($tmp_config));
        
        return "{$prefix}midcom-exec-midcom.helper.datamanager2/fckeditor/config.php?config_str={$config_str}";
    }
    
    /**
     * This changes the defaults for the textarea size to something more usable for a
     * WYSIWYG editor.
     *
     * The systemwide defaults for the theme and the fck config snippet will be loaded
     * from the config file at this point.
     *
     * @access private
     */
    function _on_configuring()
    {
        parent::_on_configuring();
        
        $this->configuration['fckconfig'] = array
        (
            'SkinPath' => 'editor/skins/office2003/',
            'DefaultLanguage' => 'en-uk',
            'UseBROnCarriageReturn' => 'false',
            'StartupFocus' => 'false',
        );
        
        $this->_static_toolbar_sets = $this->_config->get('fck_static_toolbar_sets');
        $this->_default_toolbar_set_contents = $this->_config->get('fck_default_toolbar_set_contents');
        $this->fck_config_snippet = $this->_config->get('fck_default_config_snippet');
        
        $this->fckeditor_path = MIDCOM_ROOT . '/midcom/helper/datamanager2/static/fckeditor/';
        $this->configuration['basepath'] = MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/fckeditor/';
    }

    /**
     * This is called during intialization.
     * @return boolean always true
     */
    function _on_initialize ()
    {        
        if ($this->_initialize_dependencies)
        {
            $this->_add_initscript();
        }

        return true;
    }

    /**
     *
     */
    function add_elements_to_form()
    {
        $this->_form->registerElementType(
            'fckeditor',
            $this->fckeditor_path . 'HTML_Quickform_fckeditor.php',
            'HTML_Quickform_fckeditor'
        );
        
        if (!$this->_initialize_dependencies)
        {
            $this->_add_initscript();
        }
        
        $attributes = Array
        (
            'class' => 'fckeditor',
            'id'    => "{$this->_namespace}{$this->name}",
        );

        $this->_editor = $this->_form->addElement(
            'fckeditor',
            $this->name,
            $this->_translate($this->_field['title']),
            $attributes
        );

        $this->_editor->setFCKProps(
            $this->configuration['basepath'],
            $this->configuration['toolbar_set'],
            $this->configuration['width'],
            $this->configuration['height'],
            $this->configuration['fckconfig']
        );
        
        $this->_form->applyFilter($this->name, 'trim');

        if ($this->maxlength > 0)
        {
            $errormsg = sprintf($this->_l10n->get('type text: value is longer then %d characters'), $this->maxlength);
            $this->_form->addRule($this->name, $errormsg, 'maxlength', $this->maxlength);
        }

    }

}

?>