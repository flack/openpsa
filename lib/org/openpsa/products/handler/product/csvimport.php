<?php
/**
 * @package org.openpsa.products
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package org.openpsa.products
 */
class org_openpsa_products_handler_product_csvimport extends midcom_baseclasses_components_handler
{
    private $_datamanager = null;
    private $_products_processed = array();

    private function _prepare_handler($args)
    {
        // Mass importing is for now better left for admins only
        // TODO: Add smarter per-type ACL checks
        midcom::get('auth')->require_admin_user();
        $this->_request_data['type'] = 'product';

        $this->_request_data['import_status'] = array
        (
            'updated' => 0,
            'created' => 0,
            'failed_create' => 0,
            'failed_update' => 0,
        );

        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_request_data['schemadb_product']);

        midcom::get()->disable_limits();
    }

    private function _datamanager_process(&$productdata, &$object)
    {
        $data =& $this->_request_data;
        // Load datamanager2 for the object
        if (!$this->_datamanager->set_storage($object))
        {
            return false;
        }

        // Set all given values into DM2
        foreach ($productdata as $key => $value)
        {
            if (   array_key_exists($key, $this->_datamanager->types)
                && !in_array($key, $data['fields_to_skip']))
            {
                $this->_datamanager->types[$key]->convert_from_csv($value);
            }
        }

        return $this->_datamanager->save();
    }

    /**
     * Converts given data to the servers current charset.
     *
     * In case of failure returns the data as-is.
     *
     * @param string $data the data to convert
     * @return string converted data
     */
    private function _charset_convert($data)
    {
        if (   !function_exists('mb_detect_encoding')
            || !function_exists('iconv'))
        {
            return $data;
        }

        static $target_charset = null;
        static $detect_list = null;
        static $iconv_append = null;
        static $iconv_target = null;
        if (empty($target_charset))
        {
            $target_charset = midcom::get('i18n')->get_current_charset();
        }
        if (empty($detect_list))
        {
            $detect_list = $this->_config->get('mb_detect_encoding_list');
        }
        if (empty($iconv_append))
        {
            $iconv_append = $this->_config->get('iconv_append_target');
        }
        if (empty($iconv_target))
        {
            $iconv_target = $target_charset . $iconv_append;
        }

        $encoding = mb_detect_encoding($data, $detect_list);
        if ($encoding === $target_charset)
        {
            return $data;
        }

        // Ragnaroek-todo: Use try-catch to prevent error_handler trouble
        $stat = @iconv($encoding, $iconv_target, $data);
        if (empty($stat))
        {
            return $data;
        }
        $data = $stat;
        unset($stat);
        return $data;
    }

    private function _import_product($productdata)
    {
        $data =& $this->_request_data;

        // Convert fields from latin-1 to MidCOM charset (usually utf-8)
        foreach ($productdata as $key => $value)
        {
            // FIXME: It would be immensely more efficient to do this per-file or even per row rather than per field
            $productdata[$key] = $this->_charset_convert($value);
        }

        $product = null;
        $new = false;

        // GUID has precedence
        if (!empty($productdata['GUID']))
        {
            $product = new org_openpsa_products_product_dba($productdata['GUID']);
            if ($product->guid != $productdata['GUID'])
            {
                // Could not fetch correct product
                unset($product);
            }
        }
        // FIXME, we should check the the field that has storaget set to code, not just the field 'code'
        else if (isset($productdata['code']))
        {
            // FIXME: the product group should be taken into account here, codes are quaranteed to be unique only within the group
            $qb = org_openpsa_products_product_dba::new_query_builder();
            $qb->add_constraint('code', '=', (string) $productdata['code']);

            $products = $qb->execute();
            if (count($products) > 0)
            {
                // Match found, use it
                $product = $products[0];
            }
        }

        if (!$product)
        {
            // We didn't have group matching the code in DB. Create a new one.
            $product = new org_openpsa_products_product_dba();

            $product->productGroup = $data['new_products_product_group'];

            if (!$product->create())
            {
                debug_add("Failed to create product, reason " . midcom_connection::get_error_string());
                $this->_request_data['import_status']['failed_create']++;
                return false;
                // This will skip to next
            }
            $product->set_parameter('midcom.helper.datamanager2', 'schema_name', $data['schema']);
            $new = true;
        }

        // Map products without group to the "new products" group
        if (   empty($product->productGroup)
            && !empty($data['new_products_product_group']))
        {
            $product->productGroup = $data['new_products_product_group'];
        }

        if (!$this->_datamanager_process($productdata, $product))
        {
            if ($new)
            {
                $product->delete();
                $this->_request_data['import_status']['failed_create']++;
            }
            else
            {
                $this->_request_data['import_status']['failed_update']++;
            }
            return false;
        }

        $this->_products_processed[$product->code] = $product;

        if ($new)
        {
            $this->_request_data['import_status']['created']++;
        }
        else
        {
            $this->_request_data['import_status']['updated']++;
        }

        return $product;
    }

    private function _get_product_group_tree($up)
    {
        $groups = array();

        $qb = org_openpsa_products_product_group_dba::new_query_builder();
        $qb->add_constraint('up', '=', $up);
        $qb->add_order('metadata.score');
        $results = $qb->execute();

        foreach ($results as $result)
        {
            $groups[$result->id] = midcom_helper_reflector_tree::resolve_path($result);
            $subgroups = $this->_get_product_group_tree($result->id);
            foreach ($subgroups as $k => $v)
            {
                $groups[$k] = $v;
            }
        }
        return $groups;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_csv_select($handler_id, array $args, array &$data)
    {
        $this->_prepare_handler($args);

        $data['product_groups'] = array();

        $up = 0;

        if ($this->_config->get('root_group') != 0)
        {
            $root_group_guid = $this->_config->get('root_group');
            $root_group_obj = org_openpsa_products_product_group_dba::get_cached($root_group_guid);
            $up = $root_group_obj->up;
        }

        $data['product_groups'] = $this->_get_product_group_tree($up);

        if (isset($_POST['org_openpsa_products_import_schema']))
        {
            $data['schema'] = $_POST['org_openpsa_products_import_schema'];
        }
        else
        {
            $data['schema'] = 'default';
        }
        $this->_datamanager->set_schema($data['schema']);

        if (array_key_exists('org_openpsa_products_import_separator', $_POST))
        {
            $data['time_start'] = time();

            $data['rows'] = array();

            switch ($_POST['org_openpsa_products_import_separator'])
            {
                case ';':
                    $data['separator'] = ';';
                    break;

                case ',':
                default:
                    $data['separator'] = ',';
                    break;
            }

            $data['new_products_product_group'] = $_POST['org_openpsa_products_import_new_products_product_group'];

            if (is_uploaded_file($_FILES['org_openpsa_products_import_upload']['tmp_name']))
            {
                // Copy the file for later processing
                $data['tmp_file'] = tempnam(midcom::get('config')->get('midcom_tempdir'), 'org_openpsa_products_import_csv');
                $src = fopen($_FILES['org_openpsa_products_import_upload']['tmp_name'], 'r');
                $dst = fopen($data['tmp_file'], 'w+');
                while (! feof($src))
                {
                    $buffer = fread($src, 131072); /* 128 kB */
                    fwrite($dst, $buffer, 131072);
                }
                fclose($src);
                fclose($dst);

                // Read cell headers from the file
                $read_rows = 0;
                $handle = fopen($_FILES['org_openpsa_products_import_upload']['tmp_name'], 'r');
                $separator = $data['separator'];
                $total_columns = 0;
                while (   $read_rows < 2
                       && $csv_line = fgetcsv($handle, 3000, $separator))
                {
                    if ($total_columns == 0)
                    {
                        $total_columns = count($csv_line);
                    }
                    $columns_with_content = 0;
                    foreach ($csv_line as $value)
                    {
                        if ($value != '')
                        {
                            $columns_with_content++;
                        }
                    }
                    $percentage = round(100 / $total_columns * $columns_with_content);

                    if ($percentage >= $this->_config->get('import_csv_data_percentage'))
                    {
                        $data['rows'][] = $csv_line;
                        $read_rows++;
                    }
                }
            }

            $data['time_end'] = time();
        }
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_csv_select($handler_id, array &$data)
    {
        if (array_key_exists('rows', $data))
        {
            // Present user with the field matching form
            $data['schemadb'] = $data['schemadb_product'];
            midcom_show_style('show-import-csv-select');
        }
        else
        {
            // Present user with upload form
            midcom_show_style('show-import-csv-form');
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_csv($handler_id, array $args, array &$data)
    {
        $this->_prepare_handler($args);

        $data['groups'] = array();

        if (!array_key_exists('org_openpsa_products_import_separator', $_POST))
        {
            throw new midcom_error('No CSV separator specified.');
        }

        if (!array_key_exists('org_openpsa_products_import_schema', $_POST))
        {
            throw new midcom_error('No schema specified.');
        }

        if (!file_exists($_POST['org_openpsa_products_import_tmp_file']))
        {
            throw new midcom_error('No CSV file available.');
        }

        $data['fields_to_skip'] = explode(',', $data['config']->get('import_skip_fields'));

        $data['time_start'] = time();

        $data['rows'] = array();
        $data['separator'] = $_POST['org_openpsa_products_import_separator'];
        if (!empty($_POST['org_openpsa_products_import_new_products_product_group']))
        {
            $data['new_products_product_group'] = $_POST['org_openpsa_products_import_new_products_product_group'];
        }
        else
        {
            $data['new_products_product_group'] = 0;
        }
        $data['schema'] = $_POST['org_openpsa_products_import_schema'];
        $this->_datamanager->set_schema($data['schema']);

        // Start processing the file
        $read_rows = 0;
        $total_columns = 0;
        $handle = fopen($_POST['org_openpsa_products_import_tmp_file'], 'r');
        $separator = $data['separator'];

        while ($csv_line = fgetcsv($handle, 3000, $separator))
        {
            if ($total_columns == 0)
            {
                $total_columns = count($csv_line);
            }
            $columns_with_content = 0;
            foreach ($csv_line as $value)
            {
                if ($value != '')
                {
                    $columns_with_content++;
                }
            }
            $percentage = round(100 / $total_columns * $columns_with_content);

            if ($percentage >= $this->_config->get('import_csv_data_percentage'))
            {
                $data['rows'][] = $csv_line;
                $read_rows++;
            }
            else
            {
                // This line has no proper content, skip
                continue;
            }

            $product = array();

            if ($read_rows == 1)
            {
                // First line is headers, skip
                continue;
            }
            foreach ($csv_line as $field => $value)
            {
                // Some basic CSV format cleanup
                $value = str_replace('\\n', "\n", $value);
                $value = str_replace("\\\n", "\n", $value);

                // Process the row accordingly
                $field_matching = $_POST['org_openpsa_products_import_csv_field'][$field];
                if ($field_matching)
                {
                    $schema_field = $field_matching;

                    if (   !array_key_exists($schema_field, $data['schemadb_product'][$data['schema']]->fields)
                        && $schema_field != 'org_openpsa_products_import_parent_group')
                    {
                        // Invalid matching, skip
                        continue;
                    }

                    if (   $value == ''
                        || $value == 'NULL'
                        || preg_match('/^#+$/',  $value))
                    {
                        // No value, skip
                        continue;
                    }

                    $product[$schema_field] = $value;
                }
            }

            if (count($product) > 0)
            {
                $data['groups'][] = $product;
            }
        }

        if (count($data['groups']) > 0)
        {
            foreach ($data['groups'] as $product)
            {
                $this->_import_product($product);
            }
        }

        $data['time_end'] = time();
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_csv($handler_id, array &$data)
    {
        midcom_show_style('show-import-status');
    }
}
?>