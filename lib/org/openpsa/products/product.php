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
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_products_product';

    const DELIVERY_SINGLE = 1000;
    const DELIVERY_SUBSCRIPTION = 2000;

    /**
     * Professional services
     */
    const TYPE_SERVICE = 1000;

    /**
     * Material goods
     */
    const TYPE_GOODS = 2000;

    /**
     * Solution is a nonmaterial good
     */
    const TYPE_SOLUTION = 2001;

    public function get_path()
    {
        $path = $this->guid;
        if ($this->code)
        {
            $path = $this->code;
            try
            {
                $parent = org_openpsa_products_product_group_dba::get_cached($this->productGroup);
                $path = $parent->code . '/' . $path;
            }
            catch (midcom_error $e)
            {
                $e->log();
            }
        }
        return $path;
    }


    public function render_link()
    {
        $siteconfig = new org_openpsa_core_siteconfig();

        $products_url = $siteconfig->get_node_full_url('org.openpsa.products');
        if ($products_url)
        {
            return '<a href="' . $products_url . 'product/' . $this->guid . '/">' . $this->title . "</a>\n";
        }
        else
        {
            return $this->title;
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
        if (!empty($this->id))
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

    public static function list_products()
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

        $components = $qb->execute();
        foreach ($components as $component)
        {
            $component_list[$component->id] = "{$component->code} {$component->title}";
        }
        return $component_list;
    }
}
?>
