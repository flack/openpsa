<?php
/**
 * @package org.openpsa.sales
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

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
    private $_deliverable = null;

    /**
     * The salesproject of the deliverable
     *
     * @var org_openpsa_sales_salesproject_dba
     */
    private $_salesproject = null;

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
        $this->_request_data['deliverable'] = $this->_deliverable;
        $this->_request_data['salesproject'] = $this->_salesproject;

        if ($this->_deliverable->can_do('midgard:update')) {
            $workflow = $this->get_workflow('datamanager2');
            $this->_view_toolbar->add_item($workflow->get_button("deliverable/edit/{$this->_deliverable->guid}/", array
            (
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            )));
        }

        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $this->_request_data['projects_url'] = $siteconfig->get_node_relative_url('org.openpsa.projects');
        $this->_request_data['invoices_url'] = $siteconfig->get_node_relative_url('org.openpsa.invoices');
        if ($this->_deliverable->can_do('midgard:delete')) {
            $workflow = $this->get_workflow('delete', array('object' => $this->_deliverable));
            $this->_view_toolbar->add_item($workflow->get_button("deliverable/delete/{$this->_deliverable->guid}/"));
        }
        try {
            $this->_request_data['product'] = org_openpsa_products_product_dba::get_cached($this->_deliverable->product);
        } catch (midcom_error $e) {
            $this->_request_data['product'] = false;
        }
    }

    private function _load_schema()
    {
        $this->_request_data['schemadb_salesproject_deliverable'] = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_deliverable'));
    }

    /**
     * Looks up a deliverable to display.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_view($handler_id, array $args, array &$data)
    {
        $this->_deliverable = new org_openpsa_sales_salesproject_deliverable_dba($args[0]);
        $this->_salesproject = new org_openpsa_sales_salesproject_dba($this->_deliverable->salesproject);

        $this->_load_schema();

        $this->_request_data['controller'] = midcom_helper_datamanager2_controller::create('ajax');
        $this->_request_data['controller']->schemadb =& $this->_request_data['schemadb_salesproject_deliverable'];
        $this->_request_data['controller']->set_storage($this->_deliverable);
        $this->_request_data['controller']->process_ajax();

        org_openpsa_sales_viewer::add_breadcrumb_path($this->_deliverable, $this);

        $this->_prepare_request_data();

        $this->bind_view_to_object($this->_deliverable);

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.core/list.css");
        org_openpsa_widgets_ui::enable_ui_tab();

        midcom::get()->head->set_pagetitle("{$this->_salesproject->title}: {$this->_deliverable->title}");
    }

    /**
     * Shows the loaded deliverable.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_view($handler_id, array &$data)
    {
        // For AJAX handling it is the controller that renders everything
        $this->_request_data['view_deliverable'] = $this->_request_data['controller']->get_content_html();

        if ($this->_deliverable->orgOpenpsaObtype == org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION) {
            midcom_show_style('show-deliverable-subscription');
        } else {
            midcom_show_style('show-deliverable');
        }
    }
}
