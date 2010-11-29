<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: editarea.php 22990 2009-07-23 15:46:03Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 EditArea widget
 *
 * As with all subclasses, the actual initialization is done in the initialize() function,
 * not in the constructor, to allow for error handling.
 *
 * This widget supports all types which have a simple, plain text representation accessible
 * though their <i>value</i> member.
 *
 * <b>Available configuration options:</b>
 *
 * - <i>string language:</i> Programming language being edited by EditArea, for example php or javascript
 * - <i>int width:</i> The number of columns of the textarea, this defaults to 50.
 *   Note that this value might be overridden by CSS.
 * - <i>int height:</i> The number of rows of the textearea, this defaults to 6.
 *   Note that this value might be overridden by CSS.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_editarea extends midcom_helper_datamanager2_widget
{
    /**
     * Programming language being edited by EditArea
     *
     * for example php or javascript
     *
     * @var string
     */
    public $language = 'php';

    /**
     * Width of the textbox.
     *
     * @var int
     */
    public $width = 50;

    /**
     * Height of the textbox.
     *
     * @var int
     */
    public $height = 6;

    /**
     * Whether to actually enable editarea
     */
    var $editarea_enabled = true;

    /**
     * The initialization event handler post-processes the maxlength setting.
     *
     * @return boolean Indicating Success
     */
    public function _on_initialize()
    {
        if (   ! array_key_exists('value', $this->_type)
            || is_array($this->_type->value)
            || is_object($this->_type->value))
        {
            debug_add("Warning, the field {$this->name} does not have a value member or it is an array or object, you cannot use the text widget with it.",
                MIDCOM_LOG_WARN);
            return false;
        }

        //if (strpos($_SERVER['HTTP_USER_AGENT'], 'WebKit') !== false)
        //{
        //    // EditArea really messes up Asgard for WebKit browsers
        //    $this->editarea_enabled = false;
        //}

        if ($this->editarea_enabled)
        {
            $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/edit_area/edit_area_full.js');
        }

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
            'class' => "editarea {$this->language}",
            'id'    => "{$this->_namespace}{$this->name}",
        );

        if (!$this->editarea_enabled)
        {
            $attributes['class'] = 'longtext';
        }

        $this->_form->addElement('textarea', $this->name, $this->_translate($this->_field['title']), $attributes);
        $this->_form->applyFilter($this->name, 'trim');

        $ea_lang = "en";
        $language = $_MIDCOM->i18n->get_current_language();
        if (file_exists(MIDCOM_STATIC_ROOT . '/midcom.helper.datamanager2/edit_area/langs/' . $language . '.js'))
        {
            $ea_lang = $language;
        }

        $_MIDCOM->add_jscript("
            editAreaLoader.init({
                id : '" . $attributes['id'] . "',
                syntax: 'php',
                start_highlight: true,
                allow_toggle: false,
                show_line_colors: false,
                replace_tab_by_spaces: 4,
                fullscreen: false,
                language: '" . $ea_lang . "'
            });
        ");
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