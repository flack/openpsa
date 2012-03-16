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
class org_openpsa_products_interface extends midcom_baseclasses_components_interface
{
    public function _on_initialize()
    {
        // Define product types
        // Professional services
        define('ORG_OPENPSA_PRODUCTS_PRODUCT_TYPE_SERVICE', 1000);
        // Material goods
        define('ORG_OPENPSA_PRODUCTS_PRODUCT_TYPE_GOODS', 2000);
        // Solution is a nonmaterial good
        define('ORG_OPENPSA_PRODUCTS_PRODUCT_TYPE_SOLUTION', 2001);

        define('ORG_OPENPSA_PRODUCTS_PRODUCT_GROUP_TYPE_SMART', 1000);

        return true;
    }

    public function _on_resolve_permalink($topic, $config, $guid)
    {
        try
        {
            $productlink = new org_openpsa_products_product_link_dba($guid);
            return $this->_resolve_productlink($productlink, $topic);
        }
        catch (midcom_error $e)
        {
            try
            {
                $product = new org_openpsa_products_product_dba($guid);
                return $this->_resolve_product($product, $topic);
            }
            catch (midcom_error $e)
            {
                try
                {
                    $product_group = new org_openpsa_products_product_group_dba($guid);
                    return $this->_resolve_productgroup($product_group, $topic);
                }
                catch (midcom_error $e)
                {
                    return null;
                }
            }
        }
    }

    private function _resolve_productgroup($product_group, $topic)
    {
        $intree = false;
        $real_config = new midcom_helper_configuration($topic, 'org.openpsa.products');

        if (   $real_config->get('root_group') != null
            && $real_config->get('root_group') != 0)
        {
            $root_group = new org_openpsa_products_product_group_dba($real_config->get('root_group'));
            if ($root_group->id == $product_group->id)
            {
                $intree = true;
            }
            else
            {
                $qb_intree = org_openpsa_products_product_group_dba::new_query_builder();
                $qb_intree->add_constraint('up', 'INTREE', $root_group->id);
                $qb_intree->add_constraint('id', '=', $product_group->id);
                $results = $qb_intree->execute();

                if ($qb_intree->count() > 0)
                {
                    $intree = true;
                }
            }

            if ($intree)
            {
                if ($product_group->code)
                {
                    return "{$product_group->code}/";
                }
                else
                {
                    return "{$product_group->guid}/";
                }
            }
        }
        else
        {
            if ($product_group->code)
            {
                return "{$product_group->code}/";
            }
            else
            {
                return "{$product_group->guid}/";
            }
        }
    }

    private function _resolve_product($product, $topic)
    {
        if (!$product->productGroup)
        {
            return null;
        }
        $intree = false;
        $real_config = new midcom_helper_configuration($topic, 'org.openpsa.products');

        if (   $real_config->get('root_group') != null
            && $real_config->get('root_group') != 0)
        {
            $root_group = new org_openpsa_products_product_group_dba($real_config->get('root_group'));
            if ($root_group->id == $product->productGroup)
            {
                $intree = true;
            }
            else
            {
                $qb_intree = org_openpsa_products_product_group_dba::new_query_builder();
                $qb_intree->add_constraint('up', 'INTREE', $root_group->id);
                $qb_intree->add_constraint('id', '=', $product->productGroup);
                $results = $qb_intree->execute();

                if ($qb_intree->count() > 0)
                {
                    $intree = true;
                }
            }

            if ($intree)
            {
                $category_qb = org_openpsa_products_product_group_dba::new_query_builder();
                $category_qb->add_constraint('id', '=', $product->productGroup);
                $category = $category_qb->execute_unchecked();
                //Check if the product is in a nested category.
                if (   $category
                    && isset($category[0]->up)
                    && $category[0]->up > 0)
                {
                    $parent_category_qb = org_openpsa_products_product_group_dba::new_query_builder();
                    $parent_category_qb->add_constraint('id', '=', $category[0]->up);
                    $parent_category = $parent_category_qb->execute_unchecked();
                    if (   $parent_category
                        && isset($parent_category[0]->code))
                    {
                        return "product/{$parent_category[0]->code}/{$product->code}/";
                    }
                }
                else
                {
                    return "product/{$product->code}/";
                }
            }
            else
            {
                return null;
            }
        }
        else
        {
            return "product/{$product->guid}/";
        }
    }

    private function _resolve_productlink($productlink, $topic)
    {
        if (!$productlink->productGroup)
        {
            return null;
        }
        $intree = false;
        $real_config = new midcom_helper_configuration($topic, 'org.openpsa.products');

        if (   $real_config->get('root_group') != null
            && $real_config->get('root_group') != 0)
        {
            $root_group = new org_openpsa_products_product_group_dba($real_config->get('root_group'));
            if ($root_group->id == $productlink->productGroup)
            {
                $intree = true;
            }
            else
            {
                $qb_intree = org_openpsa_products_product_group_dba::new_query_builder();
                $qb_intree->add_constraint('up', 'INTREE', $root_group->id);
                $qb_intree->add_constraint('id', '=', $productlink->productGroup);
                $results = $qb_intree->execute();

                if ($qb_intree->count() > 0)
                {
                    $intree = true;
                }
            }

            if ($intree)
            {
                $category_qb = org_openpsa_products_product_group_dba::new_query_builder();
                $category_qb->add_constraint('id', '=', $productlink->productGroup);
                $category = $category_qb->execute_unchecked();
                //Check if the product is in a nested category.
                if (   $category
                    && !empty($category[0]->up))
                {
                    $parent_category_qb = org_openpsa_products_product_group_dba::new_query_builder();
                    $parent_category_qb->add_constraint('id', '=', $category[0]->up);
                    $parent_category = $parent_category_qb->execute_unchecked();
                    if (   $parent_category
                        && isset($parent_category[0]->code))
                    {
                        return "productlink/{$productlink->guid}/";
                    }
                }
                else
                {
                    return "productlink/{$productlink->guid}/";
                }
            }
            else
            {
                return null;
            }
        }
        else
        {
            return "productlink/{$productlink->guid}/";
        }
    }

    /**
     * Iterate over all articles and create index record using the datamanager indexer
     * method.
     */
    public function _on_reindex($topic, $config, &$indexer)
    {
        if (   !$config->get('index_products')
            && !$config->get('index_groups'))
        {
            debug_add("No indexing to groups and products, skipping", MIDCOM_LOG_WARN);
            return true;
        }
        $dms = array();
        $schemadb_group = midcom_helper_datamanager2_schema::load_database($config->get('schemadb_group'));
        $dms['group'] = new midcom_helper_datamanager2_datamanager($schemadb_group);

        $schemadb_product = midcom_helper_datamanager2_schema::load_database($config->get('schemadb_product'));
        $dms['product'] = new midcom_helper_datamanager2_datamanager($schemadb_product);

        $qb = org_openpsa_products_product_group_dba::new_query_builder();
        $topic_root_group_guid = $topic->get_parameter('org.openpsa.products', 'root_group');
        if (!mgd_is_guid($topic_root_group_guid))
        {
            $qb->add_constraint('up', '=', 0);
        }
        else
        {
            $root_group = new org_openpsa_products_product_group_dba($topic_root_group_guid);
            $qb->add_constraint('id', '=', $root_group->id);
        }
        $root_groups = $qb->execute();
        foreach ($root_groups as $group)
        {
            $this->_on_reindex_tree_iterator($indexer, $dms, $topic, $group, $topic, $config);
        }

        return true;
    }

    public function _on_reindex_tree_iterator(&$indexer, &$dms, &$topic, &$group, &$topic, &$config)
    {
        if ($dms['group']->autoset_storage($group))
        {
            if ($config->get('index_groups'))
            {
                org_openpsa_products_viewer::index($dms['group'], $indexer, $topic, $config);
            }
        }
        else
        {
            debug_add("Warning, failed to initialize datamanager for product group {$group->id}. Skipping it.", MIDCOM_LOG_WARN);
        }

        if ($config->get('index_products'))
        {
            $qb_products = org_openpsa_products_product_dba::new_query_builder();
            $qb_products->add_constraint('productGroup', '=', $group->id);
            $products = $qb_products->execute();
            unset($qb_products);
            if (is_array($products))
            {
                foreach ($products as $product)
                {
                    if (!$dms['product']->autoset_storage($product))
                    {
                        debug_add("Warning, failed to initialize datamanager for product {$product->id}. Skipping it.", MIDCOM_LOG_WARN);
                        continue;
                    }
                    org_openpsa_products_viewer::index($dms['product'], $indexer, $topic, $config);
                    unset($product);
                }
            }
            unset($products);
        }

        $subgroups = array();
        $qb_groups = org_openpsa_products_product_group_dba::new_query_builder();
        $qb_groups->add_constraint('up', '=', $group->id);
        $subgroups = $qb_groups->execute();
        unset($qb_groups);
        if (!is_array($subgroups))
        {
            return true;
        }
        foreach ($subgroups as $subgroup)
        {
            $this->_on_reindex_tree_iterator($indexer, $dms, $topic, $subgroup, $topic, $config);
            unset($subgroup);
        }
        unset($subgroups);

        return true;
    }
}
?>