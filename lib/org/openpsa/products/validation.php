<?php
/**
 * @package org.openpsa.products
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Form validation functionality
 *
 * @package org.openpsa.products
 */
class org_openpsa_products_validation
{
    /**
     * Test is code is available.
     *
     * If the formdata contains a product ID, it is ignored during the search
     *
     * @var array $fields The form's data
     * @return mixed True on success, array of error messages otherwise
     */
    public function is_code_available(array $fields)
    {
        $result = array();
        if (!empty($fields['id'])) {
            $product = org_openpsa_products_product_dba::get_cached((int) $fields['id']);
        } else {
            $product = new org_openpsa_products_product_dba;
        }
        if (!empty($fields['productGroup']['selection'])) {
            $selection = json_decode($fields['productGroup']['selection']);
            $product->productGroup = (int) current($selection);
        }
        if (!$product->validate_code($fields["code"])) {
            $result["code"] = sprintf(midcom::get()->i18n->get_string("product code %s already exists in database", "org.openpsa.products"), $fields['code']);
        }

        if (!empty($result)) {
            return $result;
        }
        return true;
    }
}
