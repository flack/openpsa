<?php
/**
 * @package org.openpsa.sales
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\grid\provider\client;
use midcom\grid\provider;

/**
 * Sales project list handler
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_handler_deliverable_list extends midcom_baseclasses_components_handler
implements client
{
    /**
     * @var org_openpsa_products_product_dba
     */
    private $_product;

    public function get_qb(string $field = null, string $direction = 'ASC', array $search = []) : midcom_core_query
    {
        $mc = org_openpsa_sales_salesproject_deliverable_dba::new_collector('product', $this->_product->id);
        if ($field !== null) {
            $mc->add_order($field, $direction);
        }

        return $mc;
    }

    public function get_row(midcom_core_dbaobject $deliverable) : array
    {
        $salesproject = $deliverable->get_parent();
        $deliverable_link = $this->router->generate('deliverable_view', ['guid' => $deliverable->guid]);
        $salesproject_link = $this->router->generate('salesproject_view', ['guid' => $salesproject->guid]);

        return [
            'id' => $deliverable->id,
            'index_title' => $deliverable->title,
            'title' => "<a href='{$deliverable_link}'>" . $deliverable->title . "</a>",
            'index_salesproject' => $salesproject->title,
            'salesproject' => "<a href='{$salesproject_link}'>" . $salesproject->title . "</a>",
            'unit' => org_openpsa_sales_viewer::get_unit_option($deliverable->unit),
            'state' => $deliverable->state,
            'fixedPrice' => !$deliverable->invoiceByActualUnits,
            'pricePerUnit' => $deliverable->pricePerUnit,
            'units' => $deliverable->invoiceByActualUnits ? $deliverable->units : $deliverable->plannedUnits,
            'invoiced' => $deliverable->invoiced
        ];
    }

    public function _handler_product(string $guid, array &$data)
    {
        $this->_product = new org_openpsa_products_product_dba($guid);

        $provider = new provider($this, 'local');
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
