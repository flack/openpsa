<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: radiocheckselect.php 26507 2010-07-06 13:31:06Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 simple checkbox / radiobox select widget.
 *
 * It can only be bound to a select type (or subclass thereof), and inherits the configuration
 * from there as far as possible.
 *
 * <b>Available configuration options:</b>
 *
 * - <i>string othertext:</i> The text that is used to separate the main from the
 *   other form element. They are usually displayed in the same line. The value is passed
 *   through the standard schema localization chain.
 *
 * Note: At this time there is no support for select types with allow_other set at this time.
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_radiocheckselect extends midcom_helper_datamanager2_widget
{
    /**
     * l10n string id or direct text to use to separate the others input field from
     * the main select. Applies only for types which have allow_other set.
     *
     * @var string
     */
    var $othertext = 'widget select: other value';

    /**
     * The initialization event handler verifies the correct type.
     *
     * @return boolean Indicating Success
     */
    function _on_initialize()
    {
        if (!is_a($this->_type, 'midcom_helper_datamanager2_type_select'))
        {
            debug_add("Warning, the field {$this->name} is not a select type or subclass thereof, you cannot use the radiocheckbox widget with it.",
                MIDCOM_LOG_WARN);
            return false;
        }

        if ($this->_type->allow_other)
        {
            _midcom_stop_request("Allow Other support for radiocheckselect widget not yet implemented.");
        }

        return true;
    }

    /**
     * Adds checkboxes / radioboxes to the form.
     */
    function add_elements_to_form()
    {
        $elements = Array();
        $all_elements = $this->_type->list_all();
        foreach ($all_elements as $key => $value)
        {
            if ($this->_type->allow_multiple)
            {
                $elements[] = HTML_QuickForm::createElement
                (
                    'checkbox',
                    $key,
                    $key,
                    $this->_translate($value),
                    Array('class' => 'checkbox')
                );
            }
            else
            {
                $elements[] = HTML_QuickForm::createElement
                (
                    'radio',
                    null,
                    $key,
                    $this->_translate($value),
                    $key,
                    Array('class' => 'radiobutton')
                );
            }
        }

        $group = $this->_form->addGroup($elements, $this->name, $this->_translate($this->_field['title']), "<br />");
        if ($this->_type->allow_multiple)
        {
            $group->setAttributes(Array('class' => 'checkbox'));
        }
        else
        {
            $group->setAttributes(Array('class' => 'radiobox'));
        }
    }

    /**
     * The defaults of the widget are mapped to the current selection.
     */
    function get_default()
    {
        if ($this->_type->allow_multiple)
        {
            $defaults = Array();
            foreach ($this->_type->selection as $key)
            {
                $defaults[$key] = true;
            }
            return Array($this->name => $defaults);
        }
        else
        {
            if (count($this->_type->selection) > 0)
            {
                return Array($this->name => $this->_type->selection[0]);
            }
            else if ($this->_field['required'])
            {
                // Select the first radiobox always when this is a required field:
                $all = $this->_type->list_all();
                reset($all);
                return Array($this->name => key($all));
            }
            else
            {
                return null;
            }
        }
    }

    /**
     * The current selection is compatible to the widget value only for multiselects.
     * We need minor typecasting otherwise.
     */
    function sync_type_with_widget($results)
    {
        if ($this->_type->allow_multiple)
        {
            $this->_type->selection = Array();

            if ($results[$this->name])
            {
                $all_elements = $this->_type->list_all();
                foreach ($all_elements as $key => $value)
                {
                    if (array_key_exists($key, $results[$this->name]))
                    {
                        $this->_type->selection[] = $key;
                    }
                }
            }
        }
        else
        {
            if ($results[$this->name])
            {
                $this->_type->selection = Array($results[$this->name]);
            }
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