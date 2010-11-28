<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: hidden.php 22990 2009-07-23 15:46:03Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 simple text widget
 *
 * As with all subclasses, the actual initialization is done in the initialize() function,
 * not in the constructor, to allow for error handling.
 *
 * This widget supports all types which have a simple, plain text representation accessible
 * though their <i>value</i> member.
 *
 * <b>Available configuration options:</b>
 * At the moment, none.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_hidden extends midcom_helper_datamanager2_widget
{
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
            debug_add("Warning, the field {$this->name} does not have a value member or it is an array or object, you cannot use the text widget with it.",
                MIDCOM_LOG_WARN);
            return false;
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
            //'size' => $this->size,
            'class' => 'hiddentext',
            'id'    => "{$this->_namespace}{$this->name}",
        );
        $this->_form->addElement('hidden', $this->name, $this->_translate($this->_field['title']), $attributes);
        $this->_form->applyFilter($this->name, 'trim');

        if (is_a($this->_type, 'midcom_helper_datamanager2_type_number'))
        {
            $this->_form->addRule($this->name, $this->_translate('validation failed: numeric'), 'regex', '/^-?[0-9]*([.,][0-9]*)?$/');
            $this->_form->addFormRule(Array(&$this, 'validate_number'));
        }
    }

    /**
     * QF Validation callback used for number types. It checks the number boundaries
     * accordingly.
     */
    function validate_number($fields)
    {
        if (   $this->_type->minimum !== null
            && $fields[$this->name] < $this->_type->minimum)
        {
            return Array ($this->name => sprintf($this->_l10n->get('type number: value must not be smaller then %s'), $this->_type->minimum));
        }
        if (   $this->_type->maximum !== null
            && $fields[$this->name] > $this->_type->maximum)
        {
            return Array ($this->name => sprintf($this->_l10n->get('type number: value must not be larger then %s'), $this->_type->maximum));
        }

        return true;
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