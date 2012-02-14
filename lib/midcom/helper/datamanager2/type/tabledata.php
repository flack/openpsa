<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager2 tabledata datatype is for handling easily table data.
 *
 * Aside form the regular storage modes, tabledata can also work with m:n relation-type data:
 * If you have an object connected to multiple objects of another class via an extended
 * mapping table, the mapping table entries can be displayed as rows.
 *
 * Example: If a customer in a web shop orders a few items from the catalog, his order
 * will likely be placed in a mapping table which contains the ordered amount and other data
 * and links to the user object and the respective product object.
 *
 * This code adds a widget to an edit account form which displays the ordered items as
 * rows and the order information (quantity and so on) as columns:
 *
 * 'orders' => Array
 *       (
 *          'title' => 'My Orders',
 *          'description' => '',
 *          'helptext' => '',
 *          'type_config' => Array
 *          (
 *              'print_row_names' => true,
 *              'sortable_columns' => false,
 *              'sortable_rows' => false,
 *              'storage_mode' => 'link',
 *              'link_class' => 'orders_dba',
 *              'link_parent_field' => 'user',
 *              'link_parent_type' => 'id',
 *              'link_columns' => Array('quantity', 'shipping', 'notes'),
 *              'link_row_property' => 'product',
 *              'link_row_class' => 'product_dba',
 *              'link_row_title_field' => 'product_title',
 *          ),
 *          'type' => 'tabledata',
 *          'widget' => 'tabledata',
 *
 *      ),
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_type_tabledata extends midcom_helper_datamanager2_type
{
    /**
     * Column header data
     *
     * @var Array
     */
    public $columns = array('value' => 'value');

    /**
     * Safety for the original columns
     *
     * @var Array
     */
    public $_original_columns;

    /**
     * Row header data
     *
     * @var Array
     */
    public $rows = null;

    /**
     * Row sort order
     *
     * @var String
     */
    public $row_sort_order = 'asc';

    /**
     * Maximum amount of rows
     *
     * @var String
     */
    public $row_limit = null;

    /**
     * Maximum number of columns
     *
     * @var integer
     */
    public $column_limit = null;

    /**
     * Allow sorting of the rows
     *
     * @var boolean
     */
    public $sortable_rows = true;

    /**
     * Allow sorting of the columns
     *
     * @var boolean
     */
    public $sortable_columns = false;

    /**
     * Should the row names be printed?
     *
     * @var boolean
     */
    public $print_row_names = false;

    /**
     * Storage mode determines how the information is stored
     *
     * @var String
     */
    public $storage_mode = 'parameter';

    /**
     * Storage mode 'parameter' limiter
     *
     * @var String
     */
    public $storage_mode_parameter_limiter = '|';

    /**
     * Allow creation of new rows
     *
     * @var boolean
     */
    public $allow_new_rows = true;

    /**
     * Should adding new columns be allowed?
     *
     * @var boolean
     */
    public $allow_new_columns = false;

    /**
     * Should the column renaming be enabled
     *
     * @var boolean
     */
    public $allow_column_rename = true;

    /**
     * Parameter domain that will be used to store the data
     *
     * @var String
     */
    public $parameter_domain = 'midcom.helper.datamanager2.type.tabledata';

    /**
     * DBA class of the link object
     *
     * for storage_mode link only
     *
     * @var String
     */
    public $link_class = '';

    /**
     * The property of the link object which links to the current object
     *
     * for storage_mode link only
     *
     * @var String
     */
    public $link_parent_field = '';

    /**
     * Is the object connected to the link via GUID or ID
     *
     * for storage_mode link only
     *
     * @var String
     */
    public $link_parent_type = 'guid';

    /**
     * Link fields that should be displayed as columns
     *
     * for storage_mode link only
     *
     * @var Array
     */
    public $link_columns = 'guid';

    /**
     * The link field that should be displayed as the row title
     *
     * for storage_mode link only
     *
     * @var Array
     */
    public $link_row_property = 'guid';

    /**
     * The classname of the object used for rows
     *
     * for storage_mode link only
     *
     * @var Array
     */
    public $link_row_class = 'guid';

    /**
     * The title property of the object used for rows
     *
     * for storage_mode link only
     *
     * @var Array
     */
    public $link_row_title_field = 'guid';

    /**
     * Storage data or the data that should be stored
     *
     * @access protected
     * @var Array
     */
    var $_storage_data = array();

    /**
     * Row sort order
     *
     * @access protected
     * @var Array
     */
    var $_row_order = array();

    /**
     * Column sort order
     *
     * @access protected
     * @var Array
     */
    var $_column_order = array();

    /**
     * List of columns that shall be removed
     *
     * @var Array
     */
    public $_remove_columns = array();

    /**
     * How many rows have been printed
     *
     * @var integer
     */
    private $_row_count = 0;

    /**
     * Add JavaScript files if requested
     *
     * @return boolean Indicating success
     */
    public function _on_initialize()
    {
        $this->_original_columns = $this->columns;
        return true;
    }

    /**
     * Get the existing rows.
     *
     * @return Array containing row information
     */
    public function get_existing_rows()
    {
        $rows = $this->_load_rows();

        // Sort the rows
        if ($this->sortable_rows
            && $this->storage->object)
        {
            $order = $this->storage->object->get_parameter("{$this->parameter_domain}.type.tabledata.order", "{$this->name}:rows");

            if ($order
                && ($array = unserialize($order))
                && is_array($array))
            {
                // Reinitialize the returned array
                $new_order = array();

                // Check the order by including the array keys that exist
                foreach ($array as $row)
                {
                    if (!in_array($row, $rows))
                    {
                        continue;
                    }

                    $new_order[] = $row;
                }

                // Add the rows that weren't stored initially
                foreach ($rows as $row)
                {
                    // This key exists already, skip
                    if (in_array($row, $new_order))
                    {
                        continue;
                    }

                    $new_order[] = $row;
                }
                return $new_order;
            }
            return $rows;
        }

        // Force ascending or descending direction of the rows
        if ($this->row_sort_order
            && $this->storage_mode != 'link'
            && preg_match('/^(asc|desc)/i', $this->row_sort_order, $regs))
        {
            switch (strtolower($regs[1]))
            {
                case 'asc':
                    sort($rows);
                    break;
                case 'desc':
                    rsort($rows);
                    break;
            }
        }
        return $rows;
    }

    private function _load_rows()
    {
        $rows = array();
        switch ($this->storage_mode)
        {
            case 'serialized':
                if ($this->value)
                {
                    $temp = unserialize($this->value);
                    if (is_array($temp))
                    {
                        $rows = array_keys($temp);
                    }
                }
                break;

            case 'parameter':
                $rows = $this->_load_parameter_rows();
                break;

            case 'link':
                $rows = $this->_load_link_rows();
                break;

            default:
                throw new midcom_error("Error in type configuration: storage mode cannot be '{$this->storage_mode}'");
        }
        return $rows;
    }

    private function _load_parameter_rows()
    {
        $rows = array();
        if (empty($this->storage->object->guid))
        {
            return $rows;
        }

        // Get the row parameters with collector
        $mc = midcom_db_parameter::new_collector('parentguid', $this->storage->object->guid);

        // Add the constraints
        $mc->add_constraint('metadata.deleted', '=', 0);
        $mc->add_constraint('domain', '=', $this->parameter_domain);
        $mc->add_constraint('name', 'LIKE', "{$this->name}{$this->storage_mode_parameter_limiter}%");

        // Add orders
        $mc->add_order('metadata.revised', 'DESC');
        $mc->add_order('metadata.created', 'DESC');

        $keys = $mc->get_values('name');

        $length = strlen("{$this->name}{$this->storage_mode_parameter_limiter}");

        // List the name fields and get the row data
        foreach ($keys as $name)
        {
            $name = substr($name, $length);
            $parts = explode("{$this->storage_mode_parameter_limiter}", $name);

            if (!isset($parts[1]))
            {
                continue;
            }

            $row = preg_replace('/^row_/', '', $parts[0]);

            // Already exists, skip
            if (in_array($row, $rows))
            {
                continue;
            }

            $rows[] = $row;
        }
        return $rows;
    }

    private function _load_link_rows()
    {
        $rows = array();
        if (empty($this->storage->object->guid))
        {
            return $rows;
        }

        // Get the row parameters with collector
        $mc = midcom::get('dbfactory')->new_collector($this->link_class, $this->link_parent_field, $this->storage->object->{$this->link_parent_type});

        // Add the constraints
        $mc->add_constraint('metadata.deleted', '=', 0);

        // Add orders
        $mc->add_order($this->link_row_property . '.' . $this->link_row_title_field);
        $mc->add_order('metadata.revised', 'DESC');
        $mc->add_order('metadata.created', 'DESC');

        $keys = $mc->get_values($this->link_row_property);

        // List the name fields and get the row data
        foreach ($keys as $guid => $row_object_id)
        {
            try
            {
                $row_object = new $this->link_row_class($row_object_id);
            }
            catch (midcom_error $e)
            {
                $e->log();
                continue;
            }

            $name = $row_object->{$this->link_row_title_field};

            // Already exists, skip
            if (in_array($name, $rows))
            {
                continue;
            }

            $this->rows[$row_object_id] = $name;
            $rows[$row_object_id] = $row_object_id;
        }
        return $rows;
    }

    /**
     * Get the existing columns in the correct order
     *
     * @return Array containing column details as key => name pairs
     */
    public function get_existing_columns()
    {
        if (!$this->storage
            || !$this->storage->object
           )
        {
            return $this->columns;
        }

        if ($this->storage_mode == 'link')
          {
              $columns = array();
              foreach ($this->link_columns as $name)
              {
                  $columns[$name] = $name;
              }
              $this->columns = $columns;
              return $this->columns;
          }
        else if (!($raw_data = $this->storage->object->get_parameter("{$this->parameter_domain}.type.tabledata.order", "{$this->name}:columns")))
        {
            return $this->columns;
        }

        $unserialized = unserialize($raw_data);

        if (!$unserialized)
        {
            return $this->columns;
        }

        $columns = array();

        foreach ($unserialized as $key => $name)
        {
            $name = $this->_l10n->get($name);
            $columns[$key] = $name;
        }

        // Get the configured array keys
        foreach ($this->columns as $key => $name)
        {
            // Key exists, skip
            if (array_key_exists($key, $columns)
                || in_array($key, $columns))
            {
                continue;
            }

            $columns[$key] = $this->_l10n->get($name);
        }

        $this->columns = $columns;
        return $this->columns;
    }

    /**
     * Get row by its ID
     *
     * @param String $row    Row identifier
     * @return mixed         Array containing columns and values as key => value pairs or false on failure
     */
    public function get_row($row)
    {
        $column = array();

        // Create an empty column placeholder
        foreach ($this->columns as $column => $title)
        {
            $columns[$column] = '';
        }

        switch ($this->storage_mode)
        {
            case 'serialized':
                static $serialized_data_cached = array();

                if (isset($serialized_data_cached[$this->name]))
                {
                    $serialized_data = $serialized_data_cached[$this->name];
                }
                else
                {
                    $serialized_data = null;
                }

                if (is_null($serialized_data))
                {
                    $data = unserialize($this->value);

                    if (!$data)
                    {
                        return false;
                    }

                    $serialized_data = $data;
                }

                if (!isset($serialized_data[$row]))
                {
                    // Special handling for determined rows
                    if (isset($this->rows[$row]))
                    {
                        return $column;
                    }

                    return false;
                }

                $serialized_data_cached[$this->name] = $serialized_data;
                return $serialized_data[$row];

            case 'parameter':
                // Initialize the returned column
                $column = array();

                $mc = midcom_db_parameter::new_collector('parentguid', $this->storage->object->guid);
                $mc->add_value_property('name');
                $mc->add_value_property('value');

                // Some (hopefully older) Midgards still include deleted parameters oddly enough
                $mc->add_constraint('metadata.deleted', '=', 0);
                $mc->add_constraint('domain', '=', $this->parameter_domain);
                $mc->add_constraint('name', 'LIKE', "{$this->name}{$this->storage_mode_parameter_limiter}{$row}{$this->storage_mode_parameter_limiter}%");
                $mc->execute();

                $keys = $mc->list_keys();

                if (count($keys) === 0)
                {
                    return false;
                }

                // Length of the limiter
                $length = strlen("{$this->name}{$this->storage_mode_parameter_limiter}{$row}{$this->storage_mode_parameter_limiter}");

                foreach ($keys as $guid => $array)
                {
                    $name = $mc->get_subkey($guid, 'name');
                    $value = $mc->get_subkey($guid, 'value');

                    if (!$name
                        || !$value)
                    {
                        continue;
                    }

                    $key = substr($name, 0, $length);

                    // Not available in the column set, skipping
                    if (!isset($column[$key]))
                    {
                        continue;
                    }

                    $column[$key] = $value;
                }
        }
    }

    /**
     * Get the whole table
     *
     * @return Array containing values
     */
    public function get_table_data()
    {
        if (count($this->rows) === 0)
        {
            $rows = $this->get_existing_rows();
        }
        else
        {
            $rows = $this->rows;
        }

        $this->get_existing_columns();

        $table = array();

        foreach ($rows as $row_id)
        {
            $table[$row_id] = $this->get_row($row_id);
        }

        return $table;
    }

    /**
     * Get the value of a single cell
     *
     * @param String $row      Row name
     * @param String $column   Column name
     */
    public function get_value($row, $column)
    {
        switch ($this->storage_mode)
        {
            case 'serialized':
                // Cache the serialized data
                static $serialized_data_cached = array();

                if (isset($serialized_data_cached[$this->name]))
                {
                    $serialized_data = $serialized_data_cached[$this->name];
                }
                else
                {
                    $serialized_data = null;
                }

                if (is_null($serialized_data))
                {
                    $data = unserialize($this->value);

                    if (!$data)
                    {
                        $data = array();
                    }

                    $serialized_data = $data;
                }

                if (!isset($serialized_data[$row]))
                {
                    return '';
                }

                if (!isset($serialized_data[$row][$column]))
                {
                    return '';
                }

                $serialized_data_cached[$this->name] = $serialized_data;
                return $serialized_data[$row][$column];

            case 'parameter':
                if (!$this->storage->object)
                {
                    return '';
                }

                $value = $this->storage->object->get_parameter($this->parameter_domain, "{$this->name}{$this->storage_mode_parameter_limiter}{$row}{$this->storage_mode_parameter_limiter}{$column}");
                return $value;

            case 'link':
                $value = '';
                if (!$this->storage->object || $row == 'index')
                {
                    return $value;
                }

                // Get the row parameters with collector
                $mc = midcom::get('dbfactory')->new_collector($this->link_class, $this->link_parent_field, $this->storage->object->{$this->link_parent_type});

                // Add the constraints
                $mc->add_constraint('metadata.deleted', '=', 0);
                $mc->add_constraint($this->link_row_property, '=', $row);

                $keys = $mc->get_values($column);

                if (sizeof($keys) == 1)
                {
                    $value = array_shift($keys);
                }

                return $value;
        }
    }

    /**
     * Convert the data from storage
     *
     * @param String source
     */
    public function convert_from_storage ($source)
    {
        $this->value = $source;
    }

    /**
     * Convert the data to storage
     */
    public function convert_to_storage()
    {
        if ($this->storage_mode === 'parameter')
        {
            $this->_store_parameters();
        }
        else if ($this->storage_mode == 'link')
        {
            $this->_store_links();
        }

        // Remove the columns that should not be there
        foreach ($this->_storage_data as $row => $array)
        {
            foreach ($array as $key => $value)
            {
                if (!in_array($key, $this->_remove_columns))
                {
                    continue;
                }

                unset($this->_storage_data[$row][$key]);
            }
        }

        // Store the row order if applicable
        if ($this->sortable_rows)
        {
            $this->storage->object->set_parameter("{$this->parameter_domain}.type.tabledata.order", "{$this->name}:rows", serialize($this->_row_order));
        }

        // Store the column order
        if ($this->sortable_columns)
        {
            foreach ($this->_remove_columns as $column)
            {
                if (!in_array($column, $this->_column_order))
                {
                    continue;
                }

                $key = array_search($column, $this->_column_order);

                unset($this->_column_order[$key]);
            }

            $this->storage->object->set_parameter("{$this->parameter_domain}.type.tabledata.order", "{$this->name}:columns", serialize($this->_column_order));
        }

        // Always return serialized data - just in case the saving
        $this->value = serialize($this->_storage_data);

        return $this->value;
    }

    private function _store_parameters()
    {
        foreach ($this->_storage_data as $row => $array)
        {
            // Malformatted data
            if (!is_array($array))
            {
                continue;
            }

            // Skip the new row placeholder index
            if ($row === 'row_index')
            {
                unset($this->_storage_data[$row]);
                continue;
            }

            // Check that each field gets populated
            $hits = false;

            // Store each value in a parameter
            foreach ($array as $column => $value)
            {
                if ($value)
                {
                    $hits = true;
                }

                if (in_array($column, $this->_remove_columns))
                {
                    $this->storage->object->set_parameter($this->parameter_domain, "{$this->name}{$this->storage_mode_parameter_limiter}{$row}{$this->storage_mode_parameter_limiter}{$column}", '');
                }
                else
                {
                    $this->storage->object->set_parameter($this->parameter_domain, "{$this->name}{$this->storage_mode_parameter_limiter}{$row}{$this->storage_mode_parameter_limiter}{$column}", $value);
                }
            }

            if (!$hits)
            {
                $key = array_search($row, $this->_row_order);
                unset($this->_row_order[$key]);
                unset($this->_storage_data[$row]);
            }
        }

        // Empty the parameters that are no longer needed
        foreach ($this->storage->object->list_parameters($this->parameter_domain) as $name => $value)
        {
            $temp = explode($this->storage_mode_parameter_limiter, $name);

            // Not this field, skip this
            if ($temp[0] !== $this->name)
            {
                continue;
            }

            // Broken entry?
            if (!isset($temp[1]))
            {
                continue;
            }

            // Row found in the request list
            if (isset($this->_storage_data[$temp[1]]))
            {
                continue;
            }

            // Row not found from the posted request, erase the parameter
            $this->storage->object->set_parameter($this->parameter_domain, $name, '');
        }
    }

    private function _store_links()
    {
        $mgdschema_classname = $_MIDCOM->dbclassloader->get_mgdschema_class_name_for_midcom_class($this->link_class);
        $ref = new midgard_reflection_property($mgdschema_classname);

        $type_map = Array();

        foreach ($this->link_columns as $column)
        {
            $type_map[$column] = $ref->get_midgard_type($column);
        }
        $current_selection = array();

        unset($this->_storage_data['index']);
        foreach ($this->_storage_data as $link_row_id => $values)
        {
            if (array_key_exists($this->link_row_property, $values))
            {
                $link_row_id = $values[$this->link_row_property];
            }
            $current_selection[] = $link_row_id;

            $link_object = null;
            $needs_update = false;

            $qb = midcom::get('dbfactory')->new_query_builder($this->link_class);
            $qb->add_constraint($this->link_parent_field, '=', $this->storage->object->{$this->link_parent_type});
            $qb->add_constraint($this->link_row_property, '=', $link_row_id);
            $results = $qb->execute();

            if (sizeof($results) > 0)
            {
                $link_object = $results[0];
            }
            else
            {
                $link_object = new $this->link_class;
                $link_object->{$this->link_parent_field} = $this->storage->object->{$this->link_parent_type};
                $link_object->{$this->link_row_property} = $link_row_id;
                $link_object->create();
            }

            foreach ($type_map as $key => $type)
            {
                if ($key == $this->link_row_property)
                {
                    continue;
                }

                $value = (isset($values[$key])) ? $values[$key] : false;
                switch ($type)
                {
                    case MGD_TYPE_INT:
                        $value = (int)$value;
                        break;
                    case MGD_TYPE_FLOAT:
                        $value = (float)$value;
                        break;
                }

                if ($link_object->$key != $value)
                {
                    $needs_update = true;
                    $link_object->$key = $value;
                }
            }
            if ($needs_update)
            {
                $link_object->update();
            }
        }

        $qb = midcom::get('dbfactory')->new_query_builder($this->link_class);
        $qb->add_constraint($this->link_parent_field, '=', $this->storage->object->{$this->link_parent_type});
        if (count($current_selection))
        {
            $qb->add_constraint($this->link_row_property, 'NOT IN', $current_selection);
        }
        $links_todelete = $qb->execute();
        foreach ($links_todelete as $link_object)
        {
            $link_object->delete();
        }
    }

    /**
     * HTML output
     *
     * @return String    Output string
     */
    public function convert_to_html()
    {
        // Get the column order and added columns
        $this->get_existing_columns();

        $value = unserialize($this->value);
        $output = '';

        if (!$value)
        {
            return '';
        }

        $output .= "<table class=\"midcom_helper_datamanager2_widget_tabledata {$this->name}\">\n";
        $output .= "    <thead>\n";
        $output .= "        <tr>\n";

        if ($this->print_row_names)
        {
            $output .= "            <th class=\"label_column\">\n";
            $output .= "            </th>\n";
        }

        // Table headers
        foreach ($this->columns as $column => $title)
        {
            $output .= "            <th class=\"{$column}\">\n";
            $output .= "                " . $this->_l10n->get($title) . "\n";
            $output .= "            </th>\n";
        }

        $output .= "        </tr>\n";
        $output .= "    </thead>\n";
        $output .= "    <tbody>\n";

        $this->_row_count = 1;

        // Loop through the rows
        foreach ($this->get_existing_rows() as $row)
        {
            if ($this->_row_count % 2 === 0)
            {
                $class = 'even';
            }
            else
            {
                $class = 'odd';
            }

            ++$this->_row_count;

            $output .= "        <tr class=\"{$row} {$class}\">\n";

            if ($this->print_row_names)
            {
                $row_title = $row;

                if (isset($this->rows[$row]))
                {
                    $row_title = $this->rows[$row];
                }

                $output .= "            <th class=\"label_column\">\n";
                $output .= "                " . $this->_l10n->get($row_title) . "\n";
                $output .= "            </th>\n";
            }

            foreach ($this->columns as $column => $title)
            {
                $output .= "            <td class=\"{$column}\">\n";
                $output .= "                " . $this->get_value($row, $column) . "\n";
                $output .= "            </td>\n";
            }

            $output .= "        </tr>\n";
        }

        $output .= "    </tbody>\n";
        $output .= "</table>\n";

        return $output;
    }

    function convert_from_csv ($source)
    {
        throw new midcom_error('Not implemented yet.');
    }

    function convert_to_csv()
    {
        throw new midcom_error('Not implemented yet.');
    }
}
?>