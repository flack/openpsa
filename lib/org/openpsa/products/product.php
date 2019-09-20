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
 * @property integer $productGroup
 * @property string $code
 * @property string $title
 * @property string $description
 * @property float $price
 * @property string $unit
 * @property string $cost
 * @property string $costType
 * @property integer $start
 * @property integer $end
 * @property integer $owner
 * @property integer $supplier
 * @property integer $delivery
 * @property integer $orgOpenpsaObtype
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

    public function render_link() : string
    {
        $siteconfig = new org_openpsa_core_siteconfig();

        if ($products_url = $siteconfig->get_node_full_url('org.openpsa.products')) {
            return '<a href="' . $products_url . 'product/' . $this->guid . '/">' . $this->title . "</a>";
        }
        return $this->title;
    }

    public function _on_creating()
    {
        if (!$this->validate_code($this->code)) {
            midcom_connection::set_error(MGD_ERR_OBJECT_NAME_EXISTS);
            return false;
        }
        return true;
    }

    public function _on_updating()
    {
        if (!$this->validate_code($this->code)) {
            midcom_connection::set_error(MGD_ERR_OBJECT_NAME_EXISTS);
            return false;
        }
        return true;
    }

    public function validate_code($code) : bool
    {
        if ($code == '') {
            return true;
        }

        // Check for duplicates
        $qb = self::new_query_builder();
        $qb->add_constraint('code', '=', $code);

        if (!empty($this->id)) {
            $qb->add_constraint('id', '<>', $this->id);
        }
        // Make sure the product is in the same product group
        $qb->add_constraint('productGroup', '=', $this->productGroup);

        return $qb->count() == 0;
    }
}
