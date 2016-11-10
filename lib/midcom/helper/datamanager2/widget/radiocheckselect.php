<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
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
     * Controls how the selection list show be rendered, 'horizontal' means all choices will be
     * in one line, 'vertical' means they will be below each other. 'auto' will choose one based
     * on the number of choices
     *
     * @var string
     */
    public $render_mode = 'auto';

    /**
     * If render_mode is set to 'auto', this is the maximum number of choices that will be rendered
     * inline
     *
     * @var int
     */
    public $list_threshold = 4;

    /**
     * The initialization event handler verifies the correct type.
     */
    public function _on_initialize()
    {
        $this->_require_type_class('midcom_helper_datamanager2_type_select');

        if ($this->_type->allow_other)
        {
            throw new midcom_error("Allow Other support for radiocheckselect widget not yet implemented.");
        }
    }

    /**
     * Adds checkboxes / radioboxes to the form.
     */
    public function add_elements_to_form($attributes)
    {
        $elements = array();
        $all_elements = $this->_type->list_all();
        foreach ($all_elements as $key => $value)
        {
            if ($this->_type->allow_multiple)
            {
                $elements[] = $this->_form->createElement
                (
                    'checkbox',
                    $key,
                    $key,
                    $this->_translate($value),
                    array('class' => 'checkbox')
                );
            }
            else
            {
                $elements[] = $this->_form->createElement
                (
                    'radio',
                    null,
                    $key,
                    $this->_translate($value),
                    $key,
                    array('class' => 'radiobutton')
                );
            }
        }

        if ($this->render_mode == 'auto')
        {
            if (sizeof($elements) > $this->list_threshold)
            {
                $this->render_mode = 'vertical';
            }
            else
            {
                $this->render_mode = 'horizontal';
            }
        }

        $separator = '<span class="separator separator-' . $this->render_mode . '"></span>';

        $group = $this->_form->addGroup($elements, $this->name, $this->_translate($this->_field['title']), $separator);
        if ($this->_type->allow_multiple)
        {
            $attributes['class'] = 'checkbox checkbox-' . $this->render_mode;
            $group->setAttributes($attributes);
        }
        else
        {
            $attributes['class'] = 'radiobox radiobox-' . $this->render_mode;
            $group->setAttributes($attributes);
        }
    }

    /**
     * The defaults of the widget are mapped to the current selection.
     */
    public function get_default()
    {
        if ($this->_type->allow_multiple)
        {
            if (sizeof($this->_type->selection) == 0)
            {
                return null;
            }
            return array($this->name => array_fill_keys($this->_type->selection, true));
        }
        if (count($this->_type->selection) > 0)
        {
            return array($this->name => $this->_type->selection[0]);
        }
        if ($this->_field['required'])
        {
            // Select the first radiobox always when this is a required field:
            $all = $this->_type->list_all();
            reset($all);
            return array($this->name => key($all));
        }
        return null;
    }

    /**
     * The current selection is compatible to the widget value only for multiselects.
     * We need minor typecasting otherwise.
     */
    public function sync_type_with_widget($results)
    {
        $this->_type->selection = array();

        if ($this->_type->allow_multiple)
        {
            if ($results[$this->name])
            {
                $all_elements = $this->_type->list_all();
                $this->_type->selection = array_keys(array_intersect_key($all_elements, $results[$this->name]));
            }
        }
        elseif ($results[$this->name] !== null)
        {
            $this->_type->selection = array($results[$this->name]);
        }
    }

    public function render_content()
    {
        if ($this->_type->allow_multiple)
        {
            $output = '<ul>';
            if (count($this->_type->selection) == 0)
            {
                $output .= '<li>' . $this->_translate('type select: no selection') . '</li>';
            }
            else
            {
                foreach ($this->_type->selection as $key)
                {
                    $output .= '<li>' . $this->_translate($this->_type->get_name_for_key($key)) . '</li>';
                }
            }
            return $output . '</ul>';
        }
        if (count($this->_type->selection) == 0)
        {
            return $this->_translate('type select: no selection');
        }
        return $this->_translate($this->_type->get_name_for_key($this->_type->selection[0]));
    }
}
