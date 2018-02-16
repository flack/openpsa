<?php
/**
 * @package org.openpsa.products
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;

/**
 * Component configuration screen.
 *
 * @package org.openpsa.products
 */
class org_openpsa_products_handler_configuration extends midcom_baseclasses_components_handler_configuration_recreate
{
    public function _load_datamanagers()
    {
        return [
            org_openpsa_products_product_group_dba::class => new datamanager($this->_request_data['schemadb_group']),
            org_openpsa_products_product_dba::class => new datamanager($this->_request_data['schemadb_product'])
        ];
    }

    private function _load_objects_group($group_id)
    {
        $product_qb = org_openpsa_products_product_dba::new_query_builder();
        $product_qb->add_constraint('productGroup', '=', $group_id);
        $objects = $product_qb->execute();

        $group_qb = org_openpsa_products_product_group_dba::new_query_builder();
        $group_qb->add_constraint('up', '=', $group_id);
        foreach ($group_qb->execute() as $group) {
            $objects[] = $group;
            $child_objects = $this->_load_objects_group($group->id);
            $objects = array_merge($objects, $child_objects);
        }

        return $objects;
    }

    public function _load_objects()
    {
        return $this->_load_objects_group($this->_request_data['root_group']);
    }
}
