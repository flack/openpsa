<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: colorpicker.php 22990 2009-07-23 15:46:03Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 colorpicker chooser widget
 *
 * Colorpicker chooser allows to
 *
 * <b>Available configuration options:</b>
 *
 * - <i>boolean show_title:</i> This flag controls whether the title field is shown or not.
 *   If this is flag, the whole title processing will be disabled. This flag is true
 *   by default.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_colorpicker extends midcom_helper_datamanager2_widget_text
{
    /**
     * Storage mode for colorpicker
     *
     * Options: 'hex', 'rgb', 'hsb' for hexagonal, red-green-blue and
     * hue-saturation-brightness respectively
     *
     * @access public
     * @var string
     */
    var $color_scheme = 'hex';

    /**
     * The size of the input box.
     *
     * @var int
     * @access public
     */
    var $size = 40;

    /**
     * The initialization event handler post-processes the maxlength setting.
     *
     * @return boolean Indicating Success
     */
    function _on_initialize()
    {
        $_MIDCOM->enable_jquery();
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/colorpicker/colorpicker.js');
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/jquery.widget.colorpicker.js');
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/colorpicker/colorpicker.css');
        return true;
    }

    /**
     * Adds a simple single-line text form element at this time.
     */
    function add_elements_to_form()
    {
        $attributes = Array
        (
            'size' => $this->size,
            'class' => "midcom_helper_datamanager2_colorpicker {$this->color_scheme}",
            'id'    => "{$this->_namespace}{$this->name}",
        );

        $this->_form->addElement('text', $this->name, $this->_translate($this->_field['title']), $attributes);
    }

    /**
     * Checks if the widget is frozen.
     *
     * The default implementation works on the default field name, usually you don't need to
     * override this function unless you have some strange form element logic.
     *
     * This maps to the HTML_QuickForm_element::isFrozen() function.
     *
     * @return boolean True if the element is frozen, false otherwise.
     */
    function is_frozen()
    {
        return false;
    }
}
?>