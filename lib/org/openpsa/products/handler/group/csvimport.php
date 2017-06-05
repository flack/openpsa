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
class org_openpsa_products_handler_group_csvimport extends midcom_baseclasses_components_handler
{
    private $_datamanager = null;

    private function _prepare_handler($args)
    {
        // Mass importing is for now better left for admins only
        // TODO: Add smarter per-type ACL checks
        midcom::get()->auth->require_admin_user();
        $this->_request_data['type'] = 'group';

        $this->_request_data['import_status'] = [
            'already_created' => 0,
            'created_new' => 0,
            'failed_create' => 0,
        ];

        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_request_data['schemadb_group']);

        midcom::get()->disable_limits();
    }

    private function _datamanager_process($groupdata, $object)
    {
        // Load datamanager2 for the object
        if (!$this->_datamanager->autoset_storage($object)) {
            return false;
        }

        // Set all given values into DM2
        foreach ($groupdata as $key => $value) {
            if (array_key_exists($key, $this->_datamanager->types)) {
                $this->_datamanager->types[$key]->value = $value;
            }
        }

        // Save the object
        return $this->_datamanager->save();
    }

    private function _import_group($groupdata)
    {
        // Convert fields from latin-1 to MidCOM charset (usually utf-8)
        foreach ($groupdata as $key => $value) {
            $groupdata[$key] = iconv('ISO-8859-1', $this->_i18n->get_current_charset(), $value);
        }

        $group = null;
        $new = false;
        if (isset($groupdata['code'])) {
            $qb = org_openpsa_products_product_group_dba::new_query_builder();
            $qb->add_constraint('code', '=', (string) $groupdata['code']);
            if ($group = $qb->get_result(0)) {
                // Match found, use it
                $this->_request_data['import_status']['already_created']++;
            }
        }

        if (!$group) {
            // We didn't have group matching the code in DB. Create a new one.
            $group = new org_openpsa_products_product_group_dba();

            if (!$group->create()) {
                debug_add("Failed to create group, reason " . midcom_connection::get_error_string());
                $this->_request_data['import_status']['failed_create']++;
                return false;
                // This will skip to next
            }
            $new = true;
            $this->_request_data['import_status']['created_new']++;
        }

        if (isset($groupdata['org_openpsa_products_import_parent_group'])) {
            // Validate and set parent group
            $qb = org_openpsa_products_product_group_dba::new_query_builder();
            $qb->add_constraint('code', '=', (string) $groupdata['org_openpsa_products_import_parent_group']);
            $parents = $qb->execute();
            if (count($parents) == 0) {
                // Invalid parent, delete
                $group->delete();
                $this->_request_data['import_status']['failed_create']++;
                return false;
            }

            $group->up = $parents[0]->id;
            $groupdata['up'] = $parents[0]->id;
            $group->update();
        }

        if (!$this->_datamanager_process($groupdata, $group)) {
            if ($new) {
                $group->delete();
                $this->_request_data['import_status']['failed_create']++;
            }
            return false;
        }

        return $group;
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_csv_select($handler_id, array $args, array &$data)
    {
        $this->_prepare_handler($args);

        if (array_key_exists('org_openpsa_products_import_separator', $_POST)) {
            $data['time_start'] = time();
            $data['rows'] = [];
            $data['separator'] = ($_POST['org_openpsa_products_import_separator'] == ';') ? ';' : ',';

            if (is_uploaded_file($_FILES['org_openpsa_products_import_upload']['tmp_name'])) {
                // Copy the file for later processing
                $data['tmp_file'] = tempnam(midcom::get()->config->get('midcom_tempdir'), 'org_openpsa_products_import_csv');
                copy($_FILES['org_openpsa_products_import_upload']['tmp_name'], $data['tmp_file']);

                // Read cell headers from the file
                $read_rows = 0;
                $handle = fopen($_FILES['org_openpsa_products_import_upload']['tmp_name'], 'r');
                $separator = $data['separator'];
                $total_columns = 0;
                while (   $read_rows < 2
                       && $csv_line = fgetcsv($handle, 1000, $separator)) {
                    if ($total_columns == 0) {
                        $total_columns = count($csv_line);
                    }
                    $columns_with_content = count(array_filter($csv_line));
                    $percentage = round(100 / $total_columns * $columns_with_content);

                    if ($percentage >= 20) {
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
        if (array_key_exists('rows', $data)) {
            // Present user with the field matching form
            $data['schemadb'] = $data['schemadb_group'];
            midcom_show_style('show-import-csv-select');
        } else {
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

        $data['groups'] = [];

        if (!array_key_exists('org_openpsa_products_import_separator', $_POST)) {
            throw new midcom_error('No CSV separator specified.');
        }

        if (!file_exists($_POST['org_openpsa_products_import_tmp_file'])) {
            throw new midcom_error('No CSV file available.');
        }

        $data['time_start'] = time();

        $data['rows'] = [];
        $data['separator'] = $_POST['org_openpsa_products_import_separator'];

        // Start processing the file
        $read_rows = 0;
        $total_columns = 0;
        $handle = fopen($_POST['org_openpsa_products_import_tmp_file'], 'r');
        $separator = $data['separator'];

        while ($csv_line = fgetcsv($handle, 1000, $separator)) {
            if ($total_columns == 0) {
                $total_columns = count($csv_line);
            }
            $columns_with_content = count(array_filter($csv_line));
            $percentage = round(100 / $total_columns * $columns_with_content);

            if ($percentage < 20) {
                // This line has no proper content, skip
                continue;
            }
            $data['rows'][] = $csv_line;
            $read_rows++;

            $group = [];

            if ($read_rows == 1) {
                // First line is headers, skip
                continue;
            }
            foreach ($csv_line as $field => $value) {
                // Process the row accordingly
                if ($field_matching = $_POST['org_openpsa_products_import_csv_field'][$field]) {
                    $schema_field = $field_matching;

                    if (   !array_key_exists($schema_field, $data['schemadb_group']['default']->fields)
                        && $schema_field != 'org_openpsa_products_import_parent_group') {
                        // Invalid matching, skip
                        continue;
                    }

                    if (   $value == ''
                        || $value == 'NULL') {
                        // No value, skip
                        continue;
                    }

                    $group[$schema_field] = $value;
                }
            }

            if (count($group) > 0) {
                $data['groups'][] = $group;
            }
        }

        $this->_import_groups($data['groups']);

        $data['time_end'] = time();
    }

    private function _import_groups(array $groups, $level = 0)
    {
        $secondary_groups = [];

        foreach ($groups as $group) {
            if (isset($group['org_openpsa_products_import_parent_group'])) {
                $qb = org_openpsa_products_product_group_dba::new_query_builder();
                $qb->add_constraint('code', '=', (string) $group['org_openpsa_products_import_parent_group']);
                if ($qb->count() == 0) {
                    // Parent not found, process later
                    $secondary_groups[] = $group;
                    continue;
                }
            }
            $this->_import_group($group);
        }

        if (!empty($secondary_groups)) {
            if ($level > 5) {
                throw new midcom_error('Too many retries because of missing parent groups, ' . count($secondary_groups) . ' remaining');
            }
            $this->_import_groups($secondary_groups, ++$level);
        }
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
