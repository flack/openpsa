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
class org_openpsa_products_handler_product_csv extends midcom_baseclasses_components_handler_dataexport
{
    public function _load_schemadbs(string $handler_id, array &$args, array &$data) : array
    {
        if (isset($args[0])) {
            $group_name_to_filename = '';
            if ($root_group = $this->get_root_group()) {
                $group_name_to_filename = strtolower(str_replace(' ', '_', $root_group->code)) . '_';
            }
            $schemadb_to_use = str_replace('.csv', '', $args[0]);
            $data['filename'] = $group_name_to_filename . $schemadb_to_use . '_' . date('Y-m-d') . '.csv';
        } else {
            $schemadb_to_use = $this->_config->get('csv_export_schema');
        }

        $this->_schema = $this->_config->get('csv_export_schema');
        $schemadb = $this->_request_data['schemadb_product'];
        if ($schemadb->has($schemadb_to_use)) {
            $this->_schema = $schemadb_to_use;
        }

        return [$schemadb];
    }

    private function get_root_group() : ?org_openpsa_products_product_group_dba
    {
        if ($root_group_guid = $this->_config->get('root_group')) {
            return org_openpsa_products_product_group_dba::get_cached($root_group_guid);
        }
        return null;
    }

    public function _load_data(string $handler_id, array &$args, array &$data) : array
    {
        $qb = org_openpsa_products_product_dba::new_query_builder();
        $qb->add_order('code');
        $qb->add_order('title');

        if ($root_group = $this->get_root_group()) {
            $qb->add_constraint('productGroup', '=', $root_group->id);
        }
        $products = [];
        foreach ($qb->execute() as $product) {
            if ($product->get_parameter('midcom.helper.datamanager2', 'schema_name')) {
                $products[] = $product;
            }
        }

        return $products;
    }
}
