<?php
/**
 * @package org.openpsa.products
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM wrapped class for access to stored queries
 *
 * @package org.openpsa.products
 */
class org_openpsa_products_product_dba extends midcom_core_dbaobject
{
    var $__midcom_class_name__ = __CLASS__;
    var $__mgdschema_class_name__ = 'org_openpsa_products_product';
    
    static function new_query_builder()
    {
        return $_MIDCOM->dbfactory->new_query_builder(__CLASS__);
    }

    static function new_collector($domain, $value)
    {
        return $_MIDCOM->dbfactory->new_collector(__CLASS__, $domain, $value);
    }
    
    static function &get_cached($src)
    {
        return $_MIDCOM->dbfactory->get_cached(__CLASS__, $src);
    }
    
    function get_parent_guid_uncached()
    {
        if ($this->productGroup != 0)
        {
            $parent = new org_openpsa_products_product_group_dba($this->productGroup);
            return $parent->guid;
        }
        else
        {
            debug_add("No parent defined for this product", MIDCOM_LOG_DEBUG);
            return null;
        }
    }

    public function _on_creating()
    {
        if (!$this->validate_code($this->code))
        {
            midcom_connection::set_error(MGD_ERR_OBJECT_NAME_EXISTS);
            return false;
        }
        return true;
    }

    public function _on_updating()
    {
        if (!$this->validate_code($this->code))
        {
            midcom_connection::set_error(MGD_ERR_OBJECT_NAME_EXISTS);
            return false;
        }
        return true;
    }

    function validate_code($code)
    {
        $quickform_mode = false;

        if ($code == '')
        {
            return true;
        }

        if (is_array($code))
        {
            // This validation call was made by QuickForm
            $quickform_mode = true;
            $code = $code['code'];
        }

        // Check for duplicates
        $qb = org_openpsa_products_product_dba::new_query_builder();
        $qb->add_constraint('code', '=', $code);
        if (   isset($this)
            && $this->id)
        {
            $qb->add_constraint('id', '<>', $this->id);
            // Make sure the product is in the same product group
            $qb->add_constraint('productGroup', '=', (int)$this->productGroup);
        }
        $result = $qb->execute();
        if (count($result) > 0)
        {
            if ($quickform_mode)
            {
                $error = array
                (
                    'code' => "Product {$code} already exists in database.",
                );
                return $error;
            }
            return false;
        }
        return true;
    }

    function list_products($list_components = false)
    {
        $component_list = Array();
        $qb = org_openpsa_products_product_dba::new_query_builder();
        $qb->add_order('productGroup');
        $qb->add_order('code');
        $qb->add_order('title');
        $qb->add_constraint('start', '<=', time());
        $qb->begin_group('OR');
            /*
             * List products that either have no defined end-of-market dates
             * or are still in market
             */
            $qb->add_constraint('end', '=', 0);
            $qb->add_constraint('end', '>=', time());
        $qb->end_group();

        if (!$list_components)
        {
            $qb->add_constraint('orgOpenpsaObtype', '<>', ORG_OPENPSA_PRODUCTS_PRODUCT_TYPE_COMPONENT);
        }

        $components = $qb->execute();
        foreach ($components as $component)
        {
            $component_list[$component->id] = "{$component->code} {$component->title}";
        }
        return $component_list;
    }
}
?>