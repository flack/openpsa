<?php
/**
 * @package org.openpsa.sales
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Sales project list handler
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_handler_deliverable_list extends midcom_baseclasses_components_handler
implements org_openpsa_widgets_grid_provider_client
{
    public function get_qb($field = null, $direction = 'ASC', array $search = [])
    {
        $mc = org_openpsa_sales_salesproject_deliverable_dba::new_collector('product', $this->_product->id);
        if (!is_null($field)) {
            $mc->add_order($field, $direction);
        }

        return $mc;
    }

    public function get_row(midcom_core_dbaobject $deliverable)
    {
        $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
        $salesproject = $deliverable->get_parent();
        $deliverable_link = "<a href='{$prefix}deliverable/{$deliverable->guid}/'>" . $deliverable->title . "</a>";
        $salesproject_link = "<a href='{$prefix}salesproject/{$salesproject->guid}/'>" . $salesproject->title . "</a>";

        return [
            'id' => $deliverable->id,
            'index_title' => $deliverable->title,
            'title' => $deliverable_link,
            'index_salesproject' => $salesproject->title,
            'salesproject' => $salesproject_link,
            'unit' => org_openpsa_products_viewer::get_unit_option($deliverable->unit),
            'state' => $deliverable->state,
            'type' => $deliverable->invoiceByActualUnits,
            'pricePerUnit' => $deliverable->pricePerUnit,
            'units' => $deliverable->units,
            'invoiced' => $deliverable->invoiced
        ];
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_product($handler_id, array $args, array &$data)
    {
        $this->_product = new org_openpsa_products_product_dba($args[0]);

        $provider = new org_openpsa_widgets_grid_provider($this, 'local');
        $data['grid'] = $provider->get_grid('deliverables_product');
        $data['product'] = $this->_product;

        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $products_url = $siteconfig->get_node_full_url('org.openpsa.products');
        $this->_view_toolbar->add_item([
            MIDCOM_TOOLBAR_URL => $products_url . 'product/' . $this->_product->guid . '/',
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('go to product'),
            MIDCOM_TOOLBAR_GLYPHICON => 'cube',
        ]);

        $title = sprintf($this->_l10n->get('deliverables for product %s'), $this->_product->title);
        $this->add_breadcrumb("", $title);
        midcom::get()->head->set_pagetitle($title);

        return $this->show('show-deliverable-grid');
    }
}
