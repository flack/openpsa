<?php
/**
 * @package org.openpsa.products
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;

/**
 * @package org.openpsa.products
 */
class org_openpsa_products_interface extends midcom_baseclasses_components_interface
implements midcom_services_permalinks_resolver
{
    /**
     * @inheritdoc
     */
    public function resolve_object_link(midcom_db_topic $topic, midcom_core_dbaobject $object)
    {
        if ($object instanceof org_openpsa_products_product_dba) {
            return 'product/' . $object->guid . '/';
        }
        if ($object instanceof org_openpsa_products_product_group_dba) {
            return $this->_resolve_productgroup($object, $topic);
        }
        return null;
    }

    private function _resolve_productgroup($product_group, $topic)
    {
        $real_config = new midcom_helper_configuration($topic, 'org.openpsa.products');

        if ($real_config->get('root_group')) {
            $root_group = new org_openpsa_products_product_group_dba($real_config->get('root_group'));
            if ($root_group->id != $product_group->id) {
                $qb_intree = org_openpsa_products_product_group_dba::new_query_builder();
                $qb_intree->add_constraint('up', 'INTREE', $root_group->id);
                $qb_intree->add_constraint('id', '=', $product_group->id);

                if ($qb_intree->count() == 0) {
                    return null;
                }
            }
        }
        return "{$product_group->guid}/";
    }

    /**
     * Iterate over all articles and create index record using the datamanager indexer method.
     */
    public function _on_reindex($topic, $config, &$indexer)
    {
        if (   !$config->get('index_products')
            && !$config->get('index_groups')) {
            debug_add("No indexing to groups and products, skipping", MIDCOM_LOG_WARN);
            return true;
        }
        $dms = [
            'group' => datamanager::from_schemadb($config->get('schemadb_group')),
            'product' => datamanager::from_schemadb($config->get('schemadb_product'))
        ];
        $qb = org_openpsa_products_product_group_dba::new_query_builder();
        $topic_root_group_guid = $topic->get_parameter('org.openpsa.products', 'root_group');
        if (!mgd_is_guid($topic_root_group_guid)) {
            $qb->add_constraint('up', '=', 0);
        } else {
            $root_group = new org_openpsa_products_product_group_dba($topic_root_group_guid);
            $qb->add_constraint('id', '=', $root_group->id);
        }
        foreach ($qb->execute() as $group) {
            $this->_on_reindex_tree_iterator($indexer, $dms, $topic, $group, $config);
        }

        return true;
    }

    public function _on_reindex_tree_iterator(&$indexer, array $dms, $topic, $group, $config)
    {
        if ($config->get('index_groups')) {
            try {
                $dms['group']->set_storage($group);
                org_openpsa_products_viewer::index($dms['group'], $indexer, $topic, $config);
            } catch (midcom_error $e) {
                $e->log(MIDCOM_LOG_WARN);
            }
        }
        if ($config->get('index_products')) {
            $qb_products = org_openpsa_products_product_dba::new_query_builder();
            $qb_products->add_constraint('productGroup', '=', $group->id);

            foreach ($qb->execute() as $product) {
                try {
                    $dms['product']->set_storage($product);
                    org_openpsa_products_viewer::index($dms['product'], $indexer, $topic, $config);
                } catch (midcom_error $e) {
                    $e->log(MIDCOM_LOG_WARN);
                }
            }
        }

        $qb_groups = org_openpsa_products_product_group_dba::new_query_builder();
        $qb_groups->add_constraint('up', '=', $group->id);

        foreach ($qb_groups->execute() as $subgroup) {
            $this->_on_reindex_tree_iterator($indexer, $dms, $topic, $subgroup, $config);
        }

        return true;
    }
}
