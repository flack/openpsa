<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 colorpicker chooser widget
 *
 * Colorpicker chooser allows to
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
     * @var string
     */
    public $color_scheme = 'hex';

    /**
     * The initialization event handler post-processes the maxlength setting.
     *
     * @return boolean Indicating Success
     */
    public function _on_initialize()
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
    function add_elements_to_form($attributes)
    {
        $attributes = array_merge($attributes, array
        (
            'class' => "midcom_helper_datamanager2_colorpicker {$this->color_scheme}",
            'id'    => "{$this->_namespace}{$this->name}",
        ));

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