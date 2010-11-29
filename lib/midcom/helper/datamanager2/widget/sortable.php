<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: sortable.php 26522 2010-07-07 14:54:18Z gudd $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 sorting widget.
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
 *
 */
class midcom_helper_datamanager2_widget_sortable extends midcom_helper_datamanager2_widget_select
{
    /**
     * Sortable elements
     *
     * @var Array
     */
    private $_elements = array();

    /**
     * Select automatically every object. This is for using the widget only to sort, not to select what
     * has been sorted.
     *
     * @var boolean
     */
    public $select_all = false;

    /**
     * The initialization event handler post-processes the maxlength setting.
     *
     * @return boolean Indicating Success
     */
    function _on_initialize()
    {
        $_MIDCOM->enable_jquery();

        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.core.min.js');
        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.widget.min.js');
        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.mouse.min.js');
        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.draggable.min.js');
        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.droppable.min.js');
        $_MIDCOM->add_jsfile(MIDCOM_JQUERY_UI_URL . '/ui/jquery.ui.sortable.min.js');

        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/jquery.widget_sortable.js');

        return parent::_on_initialize();
    }

    /**
     * Adds a (multi)select widget to the form, depending on the base type config.
     */
    function add_elements_to_form()
    {
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

        // Create the sorting elements
        $this->_create_select_element();

        $this->_form->addGroup($this->_elements, $this->name, $this->_translate($this->_field['title']), ' ', false);
    }

    /**
     * Create a sortable list
     */
    private function _create_select_element()
    {
        $readonly = $this->_field['readonly'];

        if ($readonly)
        {
            $lock['text'] = ' readonly="readonly"';
            $lock['input'] = ' disabled="disabled"';
        }
        else
        {
            $lock['text'] = '';
            $lock['input'] = '';
        }

        if ($this->_type->allow_multiple)
        {
            $input_type = 'checkbox';
            $name_suffix = '[]';
        }
        else
        {
            $input_type = 'radio';
            $name_suffix = '';
        }

        // Show the help text
        // jQuery help text, hide for now
        $html  = "<p style=\"display: none;\" class=\"sortable-help-jquery\">\n";
        $html .= $this->_l10n->get('drag and drop to sort') . '.';
        $html .= "</p>\n";

        // Non-jQuery help, show on default, but hide with jQuery
        $html  .= "<p class=\"sortable-help\">\n";
        $html .= $this->_l10n->get('write the order, lower numbers are placed first') . '.';
        $html .= "</p>\n";
        $html .= "<ul id=\"{$this->name}_sortable\" class=\"midcom_helper_datamanager2_widget_sortable\">\n";

        $this->_elements['s_header'] = HTML_QuickForm::createElement('static', 's_header', '', $html);

        $i = 1;

        // Temporary array for the selection set
        $temp = array();

        $all = $this->_type->list_all();

        foreach ($this->_type->selection as $key => $value)
        {
            if (isset($all[$value]))
            {
                $temp[$value] = $all[$value];
            }
            else
            {
                $temp[$value] = $value;
            }
        }

        foreach ($this->_type->list_all() as $key => $value)
        {
            if (array_key_exists($key, $temp))
            {
                continue;
            }

            $temp[$key] = $value;
        }

        // Reset the form data
        $html = '';

        foreach ($temp as $key => $value)
        {
            if (   array_key_exists($key, $this->_type->selection)
                || $this->select_all)
            {
                $checked = ' checked="checked"';
            }
            else
            {
                $checked = '';
            }

            $html .= "    <li>\n";

            if ($this->select_all)
            {
                $html .= "            <input type=\"{$input_type}\" name=\"{$this->name}{$name_suffix}\" id=\"midcom_helper_datamanager2_widget_sortable_{$this->name}_{$i}\" value=\"{$key}\" checked=\"checked\" style=\"display: none !important;\" />\n";
            }
            else
            {
                $html .= "        <label for=\"midcom_helper_datamanager2_widget_sortable_{$this->name}_{$i}\">\n";
                $html .= "            <input type=\"{$input_type}\" name=\"{$this->name}{$name_suffix}\" id=\"midcom_helper_datamanager2_widget_sortable_{$this->name}_{$i}\" value=\"{$key}\"{$checked} />\n";
            }

            $html .= "            <input type=\"text\" name=\"{$this->name}_order[{$key}]\" value=\"{$i}\" />\n";
            $html .= "            " . $this->_translate($value). "\n";

            if (!$this->select_all)
            {
                $html .= "         </label>\n";
            }
            $html .= "    </li>\n";
            $i++;
        }

        // Add the element HTML to the form
        $this->_elements['s_body'] = HTML_QuickForm::createElement('static', 's_body', '', $html);

        $this->_elements['s_footer'] = HTML_QuickForm::createElement('static', 's_footer', '', "</ul>\n");

        if (!$readonly)
        {
            $html = "<script type=\"text/javascript\">\n";
            $html .= "    // <![CDATA[\n";
            $html .= "        jQuery('#{$this->name}_sortable').create_sortable();\n";
            $html .= "    // ]]>\n";
            $html .= "</script>\n";

            // Add the JavaScript HTML to the form
            $this->_elements['s_javascript'] = HTML_QuickForm::createElement('static', 's_body', '', $html);
        }
    }

    /**
     * Synchronize the results with the type
     */
    function sync_type_with_widget($results)
    {
        $temp = array();

        asort($results["{$this->name}_order"]);

        foreach ($results["{$this->name}_order"] as $key => $value)
        {
            if (   !isset($results[$this->name])
                || !in_array($key, $results[$this->name]))
            {
                continue;
            }

            $temp[] = $key;
        }

        $this->_type->selection = $temp;
    }
}
?>