<?php
/**
 * @package org.openpsa.sales
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;

/**
 * Deliverable display class
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_handler_deliverable_view extends midcom_baseclasses_components_handler
{
    /**
     * The deliverable to display
     *
     * @var org_openpsa_sales_salesproject_deliverable_dba
     */
    private $_deliverable;

    /**
     * The salesproject of the deliverable
     *
     * @var org_openpsa_sales_salesproject_dba
     */
    private $_salesproject;

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
        $this->_request_data['deliverable'] = $this->_deliverable;

        if ($this->_deliverable->can_do('midgard:update')) {
            $workflow = $this->get_workflow('datamanager');
            $this->_view_toolbar->add_item($workflow->get_button($this->router->generate('deliverable_edit', [
                'guid' => $this->_deliverable->guid
            ]), [
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            ]));
        }

        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $this->_request_data['projects_url'] = $siteconfig->get_node_relative_url('org.openpsa.projects');
        $this->_request_data['invoices_url'] = $siteconfig->get_node_relative_url('org.openpsa.invoices');
        if ($this->_deliverable->can_do('midgard:delete')) {
            $workflow = $this->get_workflow('delete', ['object' => $this->_deliverable]);
            $this->_view_toolbar->add_item($workflow->get_button($this->router->generate('deliverable_delete', [
                'guid' => $this->_deliverable->guid
            ])));
        }
        try {
            $this->_request_data['product'] = org_openpsa_products_product_dba::get_cached($this->_deliverable->product);
        } catch (midcom_error $e) {
            $this->_request_data['product'] = false;
        }
    }

    /**
     * Looks up a deliverable to display.
     */
    public function _handler_view(string $guid, array &$data)
    {
        $this->_deliverable = new org_openpsa_sales_salesproject_deliverable_dba($guid);
        $this->_salesproject = new org_openpsa_sales_salesproject_dba($this->_deliverable->salesproject);
        $this->set_active_leaf($this->_topic->id . ':' . $this->_salesproject->get_state());

        $data['view_deliverable'] = datamanager::from_schemadb($this->_config->get('schemadb_deliverable'))
            ->set_storage($this->_deliverable)
            ->get_content_html();

        if ($this->_config->get('sales_pdfbuilder_class')) {
            $qb = org_openpsa_sales_salesproject_offer_dba::new_query_builder();
            $qb->add_constraint('deliverables', 'LIKE', 'a:%{%:' . $this->_deliverable->id . ';%');
            $qb->add_order('metadata.revised', 'DESC');
            $data['offers'] = $qb->execute();
        }

        $this->add_breadcrumb_path();

        $this->_prepare_request_data();

        $this->bind_view_to_object($this->_deliverable);

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.core/list.css");

        midcom::get()->head->set_pagetitle("{$this->_salesproject->title}: {$this->_deliverable->title}");
    }

    /**
     * Update the context so that we get a complete breadcrumb line towards the current location.
     */
    private function add_breadcrumb_path()
    {
        $tmp = [];
        $object = $this->_deliverable;

        while ($object) {
            if (midcom::get()->dbfactory->is_a($object, org_openpsa_sales_salesproject_deliverable_dba::class)) {
                if ($object->orgOpenpsaObtype == org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION) {
                    $prefix = $this->_l10n->get('subscription');
                } else {
                    $prefix = $this->_l10n->get('single delivery');
                }
                $tmp["deliverable/{$object->guid}/"] = $prefix . ': ' . $object->title;
            } else {
                $tmp["salesproject/{$object->guid}/"] = $object->title;
            }
            $object = $object->get_parent();
        }
        $tmp = array_reverse($tmp);

        foreach ($tmp as $url => $title) {
            $this->add_breadcrumb($url, $title);
        }
    }

    /**
     * Shows the loaded deliverable.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $data The local request data.
     */
    public function _show_view($handler_id, array &$data)
    {
        if ($this->_deliverable->orgOpenpsaObtype == org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION) {
            midcom_show_style('show-deliverable-subscription');
        } else {
            midcom_show_style('show-deliverable');
        }
    }

    public function _handler_run_cycle(string $guid)
    {
        $this->_deliverable = new org_openpsa_sales_salesproject_deliverable_dba($guid);

        if (!$this->_deliverable->run_cycle()) {
            throw new midcom_error('Operation failed. Last Midgard error was: ' . midcom_connection::get_error_string());
        }
        // Get user back to the sales project
        return new midcom_response_relocate($this->router->generate('deliverable_view', ['guid' => $this->_deliverable->guid]));
    }
}
