<?php
/**
 * @package org.openpsa.products
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: csvimport.php 23975 2009-11-09 05:44:22Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package org.openpsa.products
 */
class org_openpsa_products_handler_group_csvimport extends midcom_baseclasses_components_handler
{
    var $_datamanager = null;
    var $_groups_processed = array();

    function _prepare_handler($args)
    {
        // Mass importing is for now better left for admins only
        // TODO: Add smarter per-type ACL checks
        $_MIDCOM->auth->require_admin_user();
        $this->_request_data['type'] = 'group';

        $this->_request_data['import_status'] = array
        (
            'already_created' => 0,
            'created_new' => 0,
            'failed_create' => 0,
        );

        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_request_data['schemadb_group']);

        //Disable limits
        // TODO: Could this be done more safely somehow
        @ini_set('memory_limit', -1);
        @ini_set('max_execution_time', 0);
    }

    function _datamanager_process($groupdata, $object)
    {
        // Load datamanager2 for the object
        if (!$this->_datamanager->autoset_storage($object))
        {
            return false;
        }

        // Set all given values into DM2
        foreach ($groupdata as $key => $value)
        {
            if (array_key_exists($key, $this->_datamanager->types))
            {
                $this->_datamanager->types[$key]->value = $value;
            }
        }

        // Save the object
        if (!$this->_datamanager->save())
        {
            return false;
        }

        return true;
    }


    function _import_group($groupdata)
    {
        // Convert fields from latin-1 to MidCOM charset (usually utf-8)
        foreach ($groupdata as $key => $value)
        {
            $groupdata[$key] = iconv('ISO-8859-1', $_MIDCOM->i18n->get_current_charset(), $value);
        }

        $group = null;
        $new = false;
        if (isset($groupdata['code']))
        {
            $qb = org_openpsa_products_product_group_dba::new_query_builder();
            $qb->add_constraint('code', '=', (string) $groupdata['code']);
            $groups = $qb->execute();
            if (count($groups) > 0)
            {
                // Match found, use it
                $group = $groups[0];
                $this->_request_data['import_status']['already_created']++;
            }
        }

        if (!$group)
        {
            // We didn't have group matching the code in DB. Create a new one.
            $group = new org_openpsa_products_product_group_dba();

            if (!$group->create())
            {
                debug_add("Failed to create group, reason " . midcom_connection::get_error_string());
                $this->_request_data['import_status']['failed_create']++;
                return false;
                // This will skip to next
            }
            $new = true;
            $this->_request_data['import_status']['created_new']++;
        }

        if (isset($groupdata['org_openpsa_products_import_parent_group']))
        {
            // Validate and set parent group
            $qb = org_openpsa_products_product_group_dba::new_query_builder();
            $qb->add_constraint('code', '=', (string) $groupdata['org_openpsa_products_import_parent_group']);
            $parents = $qb->execute();
            if (count($parents) == 0)
            {
                // Invalid parent, delete
                $group->delete();
                $this->_request_data['import_status']['failed_create']++;
                return false;
            }

            $group->up = $parents[0]->id;
            $groupdata['up'] = $parents[0]->id;
            $group->update();
        }

        if (!$this->_datamanager_process($groupdata, $group))
        {
            if ($new)
            {
                $group->delete();
                $this->_request_data['import_status']['failed_create']++;
            }
            return false;
        }

        $this->_groups_processed[$group->code] = $group;

        return $group;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_csv_select($handler_id, $args, &$data)
    {
        $this->_prepare_handler($args);

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


            if (is_uploaded_file($_FILES['org_openpsa_products_import_upload']['tmp_name']))
            {
                // Copy the file for later processing
                $data['tmp_file'] = tempnam($GLOBALS['midcom_config']['midcom_tempdir'], 'org_openpsa_products_import_csv');
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
                       && $csv_line = fgetcsv($handle, 1000, $separator))
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

                    if ($percentage >= 20)
                    {
                        $data['rows'][] = $csv_line;
                        $read_rows++;
                    }
                }
            }

            $data['time_end'] = time();
        }

        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_csv_select($handler_id, &$data)
    {
        if (array_key_exists('rows', $data))
        {
            // Present user with the field matching form
            $data['schemadb'] = $data['schemadb_group'];
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
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_csv($handler_id, $args, &$data)
    {
        $this->_prepare_handler($args);

        $data['groups'] = array();

        if (!array_key_exists('org_openpsa_products_import_separator', $_POST))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'No CSV separator specified.');
            // This will exit.
        }

        if (!file_exists($_POST['org_openpsa_products_import_tmp_file']))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'No CSV file available.');
            // This will exit.
        }

        $data['time_start'] = time();

        $data['rows'] = array();
        $data['separator'] = $_POST['org_openpsa_products_import_separator'];

        // Start processing the file
        $read_rows = 0;
        $total_columns = 0;
        $handle = fopen($_POST['org_openpsa_products_import_tmp_file'], 'r');
        $separator = $data['separator'];

        while ($csv_line = fgetcsv($handle, 1000, $separator))
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

            if ($percentage >= 20)
            {
                $data['rows'][] = $csv_line;
                $read_rows++;
            }
            else
            {
                // This line has no proper content, skip
                continue;
            }

            $group = array();

            if ($read_rows == 1)
            {
                // First line is headers, skip
                continue;
            }
            foreach ($csv_line as $field => $value)
            {
                // Process the row accordingly
                $field_matching = $_POST['org_openpsa_products_import_csv_field'][$field];
                if ($field_matching)
                {
                    $schema_field = $field_matching;

                    if (   !array_key_exists($schema_field, $data['schemadb_group']['default']->fields)
                        && $schema_field != 'org_openpsa_products_import_parent_group')
                    {
                        // Invalid matching, skip
                        continue;
                    }

                    if (   $value == ''
                        || $value == 'NULL')
                    {
                        // No value, skip
                        continue;
                    }

                    $group[$schema_field] = $value;
                }
            }

            if (count($group) > 0)
            {
                $data['groups'][] = $group;
            }
        }

        $secondary_groups = array();
        $tertiary_groups = array();

        if (count($data['groups']) > 0)
        {
            foreach ($data['groups'] as $group)
            {
                if (isset($group['org_openpsa_products_import_parent_group']))
                {
                    $qb = org_openpsa_products_product_group_dba::new_query_builder();
                    $qb->add_constraint('code', '=', (string) $group['org_openpsa_products_import_parent_group']);
                    if ($qb->count() == 0)
                    {
                        // Parent not found, process later
                        $secondary_groups[] = $group;
                        continue;
                    }
                }
                $this->_import_group($group);
            }
        }

        if (count($secondary_groups) > 0)
        {
            foreach ($secondary_groups as $group)
            {
                if (isset($group['org_openpsa_products_import_parent_group']))
                {
                    $qb = org_openpsa_products_product_group_dba::new_query_builder();
                    $qb->add_constraint('code', '=', (string) $group['org_openpsa_products_import_parent_group']);
                    if ($qb->count() == 0)
                    {
                        // Parent not found, process later
                        $tertiary_groups[] = $group;
                        continue;
                    }
                }
                $this->_import_group($group);
            }
        }

        $data['time_end'] = time();

        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_csv($handler_id, &$data)
    {
        midcom_show_style('show-import-status');
    }
}
?>