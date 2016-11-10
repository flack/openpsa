<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 simple checkbox
 *
 * As with all subclasses, the actual initialization is done in the initialize() function.
 *
 * This widget supports the boolean type only.
 *
 * <b>Available configuration options:</b>
 *
 * - <i>boolean show_title:</i> This flag controls whether the title field is shown or not.
 *   If this is flag, the whole title processing will be disabled. This flag is true
 *   by default.
 * - <i>array jsevents:</i> Array of event=>action pairs to control client side behavior.
 *   Ex.:
 *   <code>
 *   'onclick' => 'do_something(param1)',
 *   </code>
 *   will add "onclick='do_something(param1)'" attribute to <input> tag.
 * - <i>string description</i> Extra description of a QF element placed next to element.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_checkbox extends midcom_helper_datamanager2_widget
{
    /**
     * JS actions bound to widget
     *
     * @var array
     */
    var $jsevents=null;

    /**
     * Whether to show field title or not.
     *
     * @var array
     */
    var $show_title = true;

    /**
     * Additional text added to QF element
     *
     * @var array
     */
    var $description = '';

    /**
     * The initialization event handler validates the base type.
     */
    public function _on_initialize()
    {
        $this->_require_type_class('midcom_helper_datamanager2_type_boolean');
    }

    /**
     * Adds a simple checkbox form element.
     */
    public function add_elements_to_form($attributes)
    {
        $attributes['class'] = 'checkbox';

        $title = ($this->show_title) ? $this->_translate($this->_field['title']) : '';

        if (is_array($this->jsevents)) {
            $attributes = array_merge($attributes, $this->jsevents);
        }

        $this->_form->addElement('checkbox', $this->name, $title, $this->description, $attributes);
    }

    public function get_default()
    {
        return $this->_type->value;
    }

    public function sync_type_with_widget($results)
    {
        $element = $this->_form->getElement($this->name);
        $this->_type->value = $element->getChecked();
    }
}
