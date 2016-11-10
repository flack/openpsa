<?php
/**
 * @package org.openpsa.products
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Product management handler
 *
 * @package org.openpsa.products
 */
class org_openpsa_products_handler_product_crud extends midcom_baseclasses_components_handler_crud
{
    protected $_dba_class = 'org_openpsa_products_product_dba';

    /**
     * Return the URL to the product view handler
     *
     * @inheritdoc
     */
    public function _get_object_url(midcom_core_dbaobject $object)
    {
        return 'product/' . $object->get_path($this->_topic);
    }

    public function _update_breadcrumb($handler_id)
    {
        // Get common breadcrumb for the product
        $breadcrumb = $this->_master->update_breadcrumb_line($this->_object);

        $breadcrumb[] = array(
            MIDCOM_NAV_URL => '',
            MIDCOM_NAV_NAME => sprintf($this->_l10n_midcom->get('edit %s'), $this->_l10n->get('product')),
        );

        midcom_core_context::get()->set_custom_key('midcom.helper.nav.breadcrumb', $breadcrumb);
    }

    /**
     * Load the schema for products
     */
    public function _load_schemadb()
    {
        $this->_schemadb =& $this->_request_data['schemadb_product'];
    }
}
