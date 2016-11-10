<?php
/**
 * @package midcom.admin.folder
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 component selection widget.
 *
 * It can only be bound to a select type (or subclass thereof), and inherits the configuration
 * from there as far as possible.
 *
 * <b>Available configuration options:</b>
 *
 * - <i>int height:</i> The height of the select box, applies only for multiselect enabled
 *   boxes, the value is ignored in all other cases. Defaults to 6.
 *
 * @package midcom.admin.folder
 */
class midcom_admin_folder_selectcomponent extends  midcom_helper_datamanager2_widget_select
{
    /**
     * Adds a (multi)select widget to the form, depending on the base type config.
     */
    public function add_elements_to_form($attributes)
    {
        // Let us try to be a bit smarter here, avoiding an all-out load for read-only
        // fields.
        // TODO: This doesn't support Access control yet.

        if ($this->_field['readonly'])
        {
            $this->_all_elements = array();
            foreach ($this->_type->selection as $key)
            {
                $this->_all_elements[$key] = $this->_type->get_name_for_key($key);
            }
        }
        else
        {
            $this->_all_elements = $this->_type->list_all();
        }

        $select_attributes = array_merge($attributes, array
        (
            'class' => ($this->_type->allow_multiple) ? 'list' : 'dropdown',
        ));
        $select_attributes['class'] .= ' selectcomponent';

        $select_element = $this->_form->createElement('select', $this->name, $this->_translate($this->_field['title']),
           array(), $select_attributes);

        // Translate and add
        foreach ($this->_all_elements as $key => $value)
        {
            $option_attributes = array();
            if (midcom::get()->componentloader->get_component_icon($key, false))
            {
                $option_attributes['style'] = 'background-image: url("' . MIDCOM_STATIC_URL . '/' . midcom::get()->componentloader->get_component_icon($key) . '")';
            }

            $select_element->addOption($this->_translate($value), $key, $option_attributes);
        }

        $select_element->setMultiple($this->_type->allow_multiple);
        if ($this->_type->allow_multiple)
        {
            $select_element->setSize($this->height);
        }

        $this->_select_element = $this->_form->addElement($select_element);
    }
}
