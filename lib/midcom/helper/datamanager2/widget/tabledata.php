<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: tabledata.php 25327 2010-03-18 17:48:42Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 tabledata widget
 *
 * This widget allows to create table-like information with Datamanager
 *
 * <b>Available configuration options</b>
 *
 *
 */
class midcom_helper_datamanager2_widget_tabledata extends midcom_helper_datamanager2_widget
{
    /**
     * Row count keeps track on the amount of rows already written
     *
     * @access private
     * @var Integer
     */
    var $_row_count = 0;

    /**
     * Separate widgets for each column
     */
    public $column_widget = array();

    /**
     * Initialization script placeholder. Not yet needed.
     *
     * @access private
     * @return boolean Indicating success
     */
    function _on_initialize()
    {
        // Enable jQuery. This will not work without
        $_MIDCOM->enable_jquery();

        // Add the JavaScript file to aid in sorting and other features
        $_MIDCOM->add_jsfile(MIDCOM_STATIC_URL . '/midcom.helper.datamanager2/datamanager2.tablesorter.js');

        // Default values
        $sortable_rows = 'false';
        $sortable_columns = 'false';

        if (!$this->_type->row_limit)
        {
            $this->_type->row_limit = 0;
        }

        if (!$this->_type->column_limit)
        {
            $this->_type->column_limit = 0;
        }

        if ($this->_type->sortable_rows)
        {
            $sortable_rows = 'true';
        }

        if ($this->_type->sortable_columns)
        {
            $sortable_columns = 'true';
        }

        $preserve_columns = '|' . implode('|,|', array_keys($this->_type->columns)) . '|';

        // Configuration options
        $_MIDCOM->add_jscript("
            jQuery(document).ready(function()
            {
                jQuery('#midcom_helper_datamanager2_{$this->_type->name}_widget_tabledata')
                    .create_sortable({
                        table_id: '#midcom_helper_datamanager2_{$this->_type->name}_widget_tabledata',
                        field_name: '{$this->_type->name}',
                        max_row_count: {$this->_type->row_limit},
                        max_column_count: {$this->_type->column_limit},
                        sortable_rows: {$sortable_rows},
                        sortable_columns: {$sortable_columns},
                        preserve_columns: '{$preserve_columns}',
                        allow_delete: true
                    });
            });

            COLUMN_TITLE = '" . $this->_type->_l10n->get('please enter the column title') . "';
        ");

        return true;
    }

    /**
     * Add elements to form
     */
    public function add_elements_to_form()
    {
        // Get the correct column order
        $this->_type->get_existing_columns();

        $html  = "<table class=\"midcom_helper_datamanager2_tabledata_widget\" id=\"midcom_helper_datamanager2_{$this->name}_widget_tabledata\">\n";
        $html .= "    <thead>\n";
        $html .= "        <tr>\n";

        // Sortable table order
        if ($this->_type->sortable_rows)
        {
            $html .= "            <th class=\"index\">\n";
            $html .= "                " . $this->_l10n->get('index') . "\n";
            $html .= "            </th>\n";
        }
        // Extra column if print_row_names is set
        if ($this->_type->print_row_names)
        {
            $html .= "            <th class=\"label_column\">\n";
            $html .= "            </th>\n";
        }

        // Create the head
        foreach ($this->_type->columns as $key => $column)
        {
            if (!array_key_exists($key, $this->_type->_original_columns))
            {
                $deletable = ' deletable';
            }
            else
            {
                $deletable = '';
            }

            $html .= "            <th class=\"tabledata_header {$key}{$deletable}\">\n";

            // Add the column sort order tag
            if ($this->_type->allow_column_rename)
            {
                $html .= "                <span class=\"field_name allow_rename\" title=\"" . $this->_l10n->get('double click to edit') . "\">" . $this->_l10n->get($column) . "</span>\n";
                $html .= "                <input type=\"hidden\" class=\"field_name\" name=\"midcom_helper_datamanager2_sortable_column[{$this->name}][{$key}]\" value=\"{$column}\" />\n";
            }
            else
            {
                $html .= "                <span class=\"field_name\">" . $this->_l10n->get($column) . "</span>\n";
            }

            $html .= "            </th>\n";
        }

        if (   $this->_type->allow_new_columns
            && (   !$this->_type->column_limit
                || count($this->_type->columns) < $this->_type->column_limit))
        {
            $html .= "            <th class=\"tabledata_header add_column\"></th>\n";
        }

        $html .= "        </tr>\n";
        $html .= "    </thead>\n";
        $html .= "    <tbody>\n";

        $html .= $this->_add_rows();

        $html .= "    </tbody>\n";

        if ($this->_type->allow_new_rows)
        {
            $html .= "    <tfoot>\n";
            $html .= "        <tr id=\"new_row\">\n";
            $html .= "            <td class=\"new_row midcom_helper_datamanager2_helper_sortable\">\n";
            $html .= "                <img src=\"" . MIDCOM_STATIC_URL . "/stock-icons/16x16/list-add.png\" alt=\"" . $this->_l10n->get('add new row') . "\" class=\"add-row\" />\n";
            $html .= "                <input type=\"text\" class=\"downloads_sortable\" name=\"midcom_helper_datamanager2_sortable[{$this->name}][]\" value=\"index\" />\n";
            $html .= "            </td>\n";
            $html .= $this->_add_columns('index');
            $html .= "        </tr>\n";
            $html .= "    </tfoot>\n";
        }

        $html .= "</table>\n";

        // Add the HTML to the form
        $this->_elements['tabledata_widget'] = HTML_QuickForm::createElement('static', 'tabledata_widget', '', $html);

        $this->_group = $this->_form->addGroup($this->_elements, $this->name, $this->_translate($this->_field['title']), "\n");
    }

    /**
     * Add each row
     *
     * @access private
     */
    function _add_rows()
    {
        $html = '';
        $rows = $this->_get_rows();

        foreach ($rows as $key)
        {
            $html .= "        <tr class=\"{$key}\" id=\"row_{$key}\">\n";

            // Sortable table order
            if ($this->_type->sortable_rows)
            {
                $html .= "            <td class=\"midcom_helper_datamanager2_helper_sortable\">\n";
                $html .= "                <input type=\"text\" class=\"downloads_sortable\" name=\"midcom_helper_datamanager2_sortable[{$this->name}][]\" value=\"{$key}\" />\n";
                $html .= "            </td>\n";
            }
            if ($this->_type->print_row_names)
            {
                if (   is_array($this->_type->rows)
                    && array_key_exists($key, $this->_type->rows))
                {
                    $title = $this->_type->rows[$key];
                }
                else
                {
                    $title = $key;
                }

                $html .= "            <th>\n";
                $html .= "                " . $this->_l10n->get($title) . "\n";
                $html .= "            </th>\n";
            }

            // Add columns for the row
            $html .= $this->_add_columns($key);

            $html .= "        </tr>\n";
        }

        return $html;
    }

    /**
     * Add columns to the widget
     *
     * @access private
     * @param String $row     Name of the row
     */
    function _add_columns($row)
    {
        $html = '';

        foreach ($this->_type->columns as $column => $value)
        {
            $cell_value = $this->_type->get_value($row, $column);

            if (   isset($_REQUEST['midcom_helper_datamanager2_type_tabledata'])
                && isset($_REQUEST['midcom_helper_datamanager2_type_tabledata'][$this->name])
                && isset($_REQUEST['midcom_helper_datamanager2_type_tabledata'][$this->name][$row])
                && isset($_REQUEST['midcom_helper_datamanager2_type_tabledata'][$this->name][$row][$column]))
            {
                $cell_value = $_REQUEST['midcom_helper_datamanager2_type_tabledata'][$this->name][$row][$column];
            }

            if (   !isset($this->column_widget[$column])
                || !isset($this->column_widget[$column]['type']))
            {
                $this->column_widget[$column]['type'] = 'text';
            }

            $html .= "            <td class=\"{$column}\">\n";
            $html .= $this->_draw_column_widget($column, $cell_value, $row);
            $html .= "            </td>\n";
        }

        return $html;
    }

    /**
     * Draw the column widget
     *
     * @access private
     */
    function _draw_column_widget($column, $cell_value, $row)
    {
        switch($this->column_widget[$column]['type'])
        {
            case 'text':
                return "                <input id=\"midcom_helper_datamanager2_widget_tabledata_{$this->name}_{$row}_{$column}\" class=\"column_field tabledata_widget_text\" type=\"text\" name=\"midcom_helper_datamanager2_type_tabledata[{$this->name}][{$row}][{$column}]\" value=\"{$cell_value}\" />\n";

            case 'select':
                $html  = "               <select id=\"midcom_helper_datamanager2_widget_tabledata_{$this->name}_{$row}_{$column}\" class=\"column_field tabledata_widget_text\" name=\"midcom_helper_datamanager2_type_tabledata[{$this->name}][{$row}][{$column}]\" value=\"{$cell_value}\">\n";

                $options = array();

                if (isset($this->column_widget[$column]['options']))
                {
                    $options = $this->column_widget[$column]['options'];
                }

                // Get each select option
                foreach ($options as $key => $option)
                {
                    if ($key === $cell_value)
                    {
                        $selected = ' selected="selected"';
                    }
                    else
                    {
                        $selected = '';
                    }

                    $html .= "                   <option value=\"{$key}\"{$selected}>" . $this->_l10n->get($option) . "</option>\n";
                }

                $html .= "               </select>\n";

                return $html;

            case 'textarea':
                return "                <textarea id=\"midcom_helper_datamanager2_widget_tabledata_{$this->name}_{$row}_{$column}\" class=\"column_field tabledata_widget_textarea\" name=\"midcom_helper_datamanager2_type_tabledata[{$this->name}][{$row}][{$column}]\">{$cell_value}</textarea>\n";
        }
    }

    /**
     * Get the details on what rows should be printed
     *
     * @access private
     * @return Array          Row details
     */
    function _get_rows()
    {
        if (is_array($this->_type->rows))
        {
            $this->_type->print_row_names = true;
            return $this->_type->rows;
        }

        // Get the existing rows from the type
        $rows = $this->_type->get_existing_rows();
        // Check if there should be a new row
        if (   $this->_type->allow_new_rows
            && (   !$this->_type->row_limit
                || count($rows) < $this->_type->row_limit))
        {
            $rows[] = time() . microtime();
        }

        return $rows;
    }

    /**
     * Check if the sorted order should be returned to type
     */
    function sync_type_with_widget($results)
    {
        if (   isset($_REQUEST['midcom_helper_datamanager2_type_tabledata'])
            && isset($_REQUEST['midcom_helper_datamanager2_type_tabledata'][$this->name]))
        {
            $this->_type->_storage_data = $_REQUEST['midcom_helper_datamanager2_type_tabledata'][$this->name];
        }

        // Deleted rows
        if (   isset($_REQUEST['___midcom_helper_datamanager2_type_tabledata'])
            && isset($_REQUEST['___midcom_helper_datamanager2_type_tabledata'][$this->name])
            && is_array($_REQUEST['___midcom_helper_datamanager2_type_tabledata'][$this->name]))
        {
            foreach ($_REQUEST['___midcom_helper_datamanager2_type_tabledata'][$this->name] as $row => $columns)
            {
                foreach ($columns as $column => $value)
                {
                    $this->_type->_storage_data[$row][$column] = '';
                }
            }
        }

        if (   $this->_type->sortable_rows
            && isset($_REQUEST['midcom_helper_datamanager2_sortable'])
            && isset($_REQUEST['midcom_helper_datamanager2_sortable'][$this->name]))
        {
            foreach ($_REQUEST['midcom_helper_datamanager2_sortable'][$this->name] as $row_name)
            {
                $this->_type->_row_order[] = $row_name;
            }
        }

        if (   $this->_type->sortable_columns
            && isset($_REQUEST['midcom_helper_datamanager2_sortable_column'])
            && isset($_REQUEST['midcom_helper_datamanager2_sortable_column'][$this->name]))
        {
            foreach ($_REQUEST['midcom_helper_datamanager2_sortable_column'][$this->name] as $key => $column_name)
            {
                $this->_type->_column_order[$key] = $column_name;
            }
        }

        if (   isset($_REQUEST['midcom_helper_datamanager2_tabledata_widget_delete'])
            && isset($_REQUEST['midcom_helper_datamanager2_tabledata_widget_delete'][$this->name]))
        {
            $this->_type->_remove_columns = $_REQUEST['midcom_helper_datamanager2_tabledata_widget_delete'][$this->name];
        }
    }
}
?>