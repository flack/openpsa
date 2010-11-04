<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: markdown.php 25327 2010-03-18 17:48:42Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 simple markdown widget
 *
 * As with all subclasses, the actual initialization is done in the initialize() function,
 * not in the constructor, to allow for error handling.
 *
 * This widget supports all types which have a simple, plain text representation accessible
 * though their <i>value</i> member.
 *
 * <b>Available configuration options:</b>
 *
 * - <i>int width:</i> The number of columns of the textarea, this defaults to 50.
 *   Note that this value might be overridden by CSS.
 * - <i>int height:</i> The number of rows of the textarea, this defaults to 6.
 *   Note that this value might be overridden by CSS.
 * - <i>string wrap:</i> Controls the textbox wrapping, defaults to 'virtual' text is
 *   wrapped by the browser, but the automatic wraps are not sent to the server. You
 *   can set this to 'off' or 'physical'. If you set this to an empty string, the
 *   attribute is omitted.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_markdown extends midcom_helper_datamanager2_widget
{
    /**
     * Width of the textbox.
     *
     * @var int
     * @access public
     */
    var $width = 50;

    /**
     * Height of the textbox.
     *
     * @var int
     * @access public
     */
    var $height = 6;

    /**
     * Wrapping mode of the textbox.
     *
     * @var string
     * @access public
     */
    var $wrap = 'virtual';
    
    /**
     * The initialization event handler post-processes the maxlength setting.
     *
     * @return boolean Indicating Success
     */
    function _on_initialize()
    {
        if (   ! array_key_exists('value', $this->_type)
            || is_array($this->_type->value)
            || is_object($this->_type->value))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Warning, the field {$this->name} does not have a value member or it is an array or object, you cannot use the text widget with it.",
                MIDCOM_LOG_WARN);
            debug_pop();
            return false;
        }

        $_MIDCOM->enable_jquery();
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/markitup/jquery.markitup.pack.js');
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/markitup/sets/markdown/set.js');
        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/markitup/skins/markitup/style.css',
            )
        );
        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/markitup/sets/markdown/style.css',
            )
        );
        
        return true;
    }

    /**
     * Adds a simple single-line text form element at this time.
     */
    function add_elements_to_form()
    {
        $attributes = Array
        (
            'rows' => $this->height,
            'cols' => $this->width,
            'class' => 'longtext',
            'id'    => "{$this->_namespace}{$this->name}",
        );
        if ($this->wrap != '')
        {
            $attributes['wrap'] = $this->wrap;
        }

        $elements = Array();
        $elements[] = HTML_QuickForm::createElement('textarea', $this->name, $this->_translate($this->_field['title']), $attributes);
        $this->_form->applyFilter($this->name, 'trim');
        
        $elements[] = HTML_QuickForm::createElement('static', "{$this->name}_toolbar", '', "<script> jQuery('#{$this->_namespace}{$this->name}').markItUp(mySettings);\n</script>");
        
        // Load help text
        $_MIDCOM->load_library('net.nehmer.markdown');
        // TODO: l10n
        $file = MIDCOM_ROOT . "/midcom/helper/datamanager2/documentation/markdown.en.txt";
        if (file_exists($file))
        {
            $elements[] = HTML_QuickForm::createElement('static', "{$this->name}_help", '', "<div class=\"net_nehmer_markdown_cheatsheet\" style=\"display: none;\">" . Markdown(file_get_contents($file)) . "</div>");
        }
                             
        $this->_form->addGroup($elements, $this->name, $this->_translate($this->_field['title']), ' ', false);
        $this->_form->updateElementAttr($this->name, array('class' => 'midcom_helper_datamanager2_widget_markdown'));
    }

    function get_default()
    {
        return $this->_type->value;
    }

    function sync_type_with_widget($results)
    {
        $this->_type->value = $results[$this->name];
    }
}

?>