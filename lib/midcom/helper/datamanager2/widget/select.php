<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: select.php 25987 2010-05-04 14:10:09Z bergie $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 simple select widget.
 *
 * It can only be bound to a select type (or subclass thereof), and inherits the configuration
 * from there as far as possible.
 *
 * <b>Available configuration options:</b>
 *
 * - <i>int height:</i> The height of the select box, applies only for multiselect enabled
 *   boxes, the value is ignored in all other cases. Defaults to 6.
 * - <i>string othertext:</i> The text that is used to separate the main from the
 *   other form element. They are usually displayed in the same line. The value is passed
 *   through the standard schema localization chain.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_select extends midcom_helper_datamanager2_widget
{
    /**
     * The height of the multi-select box, ignored if no multiple selection is allowed.
     *
     * @var int
     */
    var $height = 6;

    /**
     * l10n string id or direct text to use to separate the others input field from
     * the main select. Applies only for types which have allow_other set.
     *
     * @var string
     */
    var $othertext = 'widget select: other value';

    /**
     * All elements in the select list. saved for performance reasons.
     *
     * @var array
     */
    var $_all_elements = null;

    /**
     * The select element in use. This need separate tracking due to the potential
     * group code. This makes tracking of empty return values much easier. It is
     * populated during add_elements_to_form().
     *
     * @var HTML_QuickForm_Select
     */
    var $_select_element = null;

    /**
     * JS actions bound to widget
     *
     * @var array
     */
    var $jsevents = null;

    /**
     * The initialization event handler verifies the correct type.
     *
     * @return boolean Indicating Success
     */
    public function _on_initialize()
    {
        if (! is_a($this->_type, 'midcom_helper_datamanager2_type_select'))
        {
            debug_add("Warning, the field {$this->name} is not a select type or subclass thereof, you cannot use the select widget with it.",
                MIDCOM_LOG_WARN);
            return false;
        }
        return true;
    }

    /**
     * Adds a (multi)select widget to the form, depending on the base type config.
     */
    function add_elements_to_form()
    {
        // Let us try to be a bit smarter here, avoiding an all-out load for read-only
        // fields.
        // TODO: This doesn't support Access control yet.

        if ($this->_field['readonly'])
        {
            $this->_all_elements = Array();
            foreach ($this->_type->selection as $key)
            {
                $this->_all_elements[$key] = $this->_type->get_name_for_key($key);
            }
        }
        else
        {
            $this->_all_elements = $this->_type->list_all();
        }
        // Translate
        foreach ($this->_all_elements as $key => $value)
        {
            $this->_all_elements[$key] = $this->_translate($value);
        }

        $select_attributes = Array
        (
            'class' => ($this->_type->allow_multiple) ? 'list' : 'dropdown',
            'id'    => "{$this->_namespace}{$this->name}",
        );

        if (is_array($this->jsevents) && count($this->jsevents))
        {
            foreach ($this->jsevents as $event => $action)
            {
                $select_attributes[$event] = $action;
            }
        }

        $select_element = HTML_QuickForm::createElement('select', $this->name, $this->_translate($this->_field['title']),
            $this->_all_elements, $select_attributes);
        $select_element->setMultiple($this->_type->allow_multiple);
        if ($this->_type->allow_multiple)
        {
            $select_element->setSize($this->height);
        }

        if ($this->_type->allow_other)
        {
            $select_element->setName('select');
            $other_element = HTML_QuickForm::createElement('text', 'other', "Others");

            $elements = Array();
            $elements[] = $select_element;
            $elements[] = $other_element;

            $this->_select_element = $select_element;

            $separator = $this->_translate($this->othertext);
            $this->_form->addGroup($elements, $this->name, $this->_field['title'], " {$separator}: ");
        }
        else
        {
            $this->_select_element = $this->_form->addElement($select_element);
        }
    }

    /**
     * The defaults of the widget are mapped to the current selection.
     */
    function get_default()
    {
        if ($this->_type->allow_other)
        {
            return Array
            (
                $this->name => Array
                (
                    'select' => $this->_type->selection,
                    'other' => implode(',', $this->_type->others)
                ),
            );
        }
        else
        {
            if (count($this->_type->selection) > 0)
            {
                return Array($this->name => $this->_type->selection);
            }
            else if (! $this->_type->allow_multiple)
            {
                // Select the first element of a dropdown always:
                reset($this->_all_elements);
                return Array($this->name => key($this->_all_elements));
            }
        }
    }

    /**
     * The current selection is compatible to the widget value only for multiselects.
     * We need minor typecasting otherwise.
     */
    function sync_type_with_widget($results)
    {
        $selection = $this->_select_element->getSelected();
        if ($selection === null)
        {
            $selection = Array();
        }

        $this->_type->selection = $selection;
        if ($this->_type->allow_other && $results[$this->name]['other'] != '')
        {
            $this->_type->others = explode(',', $results[$this->name]['other']);
        }
        else
        {
            $this->_type->others = null;
        }
    }

    function render_content()
    {
        if ($this->_type->allow_multiple)
        {
            echo '<ul>';
            if (count($this->_type->selection) == 0)
            {
                echo '<li>' . $this->_translate('type select: no selection') . '</li>';
            }
            else
            {
                foreach ($this->_type->selection as $key)
                {
                    echo '<li>' . $this->_translate($this->_type->get_name_for_key($key)) . '</li>';
                }
            }
            echo '</ul>';
        }
        else
        {
            if (count($this->_type->selection) == 0)
            {
                echo $this->_translate('type select: no selection');
            }
            else
            {
                echo $this->_translate($this->_type->get_name_for_key($this->_type->selection[0]));
            }
        }

        if ($this->_type->allow_other)
        {
            if (! $this->_type->allow_multiple)
            {
                echo '; ';
            }
            echo $this->_translate($this->othertext) . ': ';
            echo implode(',', $this->_type->others);
        }
    }
}
?>
