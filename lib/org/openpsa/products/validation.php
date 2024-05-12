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
     * @param array $fields The form's data
     */
    public function is_code_available(array $fields)
    {
        $result = [];
        if (!empty($fields['id'])) {
            $product = org_openpsa_products_product_dba::get_cached((int) $fields['id']);
        } else {
            $product = new org_openpsa_products_product_dba;
        }

        if (!empty($fields['productGroup'])) {
            $product->productGroup = (int) $fields['productGroup'];
        }
        if (!$product->validate_code($fields["code"])) {
            $result["code"] = sprintf(midcom::get()->i18n->get_string("product code %s already exists in database", "org.openpsa.products"), $fields['code']);
        }

        return $result ?: true;
    }
}
