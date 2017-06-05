<?php
/**
 * @package org.openpsa.products
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Product display class
 *
 * @package org.openpsa.products
 */
class org_openpsa_products_handler_product_view extends midcom_baseclasses_components_handler
{
    /**
     * The product to display
     *
     * @var org_openpsa_products_product_dba
     */
    private $_product = null;

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
        $this->_request_data['product'] = $this->_product;

        if ($this->_product->can_do('midgard:update')) {
            $workflow = $this->get_workflow('datamanager2');
            $this->_view_toolbar->add_item($workflow->get_button("product/edit/{$this->_product->guid}/", [
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            ]));
        }

        if ($this->_product->can_do('midgard:delete')) {
            $workflow = $this->get_workflow('delete', ['object' => $this->_product]);
            $this->_view_toolbar->add_item($workflow->get_button("product/delete/{$this->_product->guid}/"));
        }
    }

    /**
     * Looks up a product to display.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_view($handler_id, array $args, array &$data)
    {
        if (preg_match('/_raw$/', $handler_id)) {
            midcom::get()->skip_page_style = true;
        }

        $this->_load_product($handler_id, $args);

        if (midcom::get()->config->get('enable_ajax_editing')) {
            $data['controller'] = midcom_helper_datamanager2_controller::create('ajax');
            $data['controller']->schemadb =& $data['schemadb_product'];
            $data['controller']->set_storage($this->_product);
            $data['controller']->process_ajax();
            $data['datamanager'] = $data['controller']->datamanager;
        } else {
            $data['controller'] = null;
            $data['datamanager'] = new midcom_helper_datamanager2_datamanager($data['schemadb_product']);
            if (!$data['datamanager']->autoset_storage($this->_product)) {
                throw new midcom_error("Failed to create a DM2 instance for product {$this->_product->guid}.");
            }
        }

        $this->_prepare_request_data();
        $this->bind_view_to_object($this->_product, $data['datamanager']->schema->name);

        $breadcrumb = $this->_master->update_breadcrumb_line($this->_product);
        midcom_core_context::get()->set_custom_key('midcom.helper.nav.breadcrumb', $breadcrumb);

        midcom::get()->metadata->set_request_metadata($this->_product->metadata->revised, $this->_product->guid);

        $title = $this->_config->get('product_page_title');

        if (strstr($title, '<PRODUCTGROUP')) {
            try {
                $productgroup = new org_openpsa_products_product_group_dba($this->_product->productGroup);
                $title = str_replace('<PRODUCTGROUP_TITLE>', $productgroup->title, $title);
                $title = str_replace('<PRODUCTGROUP_CODE>', $productgroup->code, $title);
            } catch (midcom_error $e) {
                $title = str_replace(['<PRODUCTGROUP_TITLE>', '<PRODUCTGROUP_CODE>'], '', $title);
            }
        }

        $title = str_replace('<PRODUCT_CODE>', $this->_product->code, $title);
        $title = str_replace('<PRODUCT_TITLE>', $this->_product->title, $title);
        $title = str_replace('<TOPIC_TITLE>', $this->_topic->extra, $title);
        org_openpsa_widgets_ui::enable_ui_tab();
        midcom::get()->head->set_pagetitle($title);
    }

    private function _load_product($handler_id, array $args)
    {
        $qb = org_openpsa_products_product_dba::new_query_builder();
        $qb->add_constraint('guid', '=', $args[0]);

        if ($this->_config->get('enable_scheduling')) {
            /* List products that either have no defined end-of-market dates
             * or are still in market
             */
            $qb->add_constraint('start', '<=', time());
            $qb->begin_group('OR');
            $qb->add_constraint('end', '=', 0);
            $qb->add_constraint('end', '>=', time());
            $qb->end_group();
        }

        $this->_product = $qb->get_result(0);

        if (!$this->_product) {
            throw new midcom_error_notfound('Product is not available (or hidden)');
        }
    }

    /**
     * Shows the loaded product.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_view($handler_id, array &$data)
    {
        if ($data['controller']) {
            // For AJAX handling it is the controller that renders everything
            $data['view_product'] = $data['controller']->get_content_html();
        } else {
            $data['view_product'] = $data['datamanager']->get_content_html();
        }
        midcom_show_style('product_view');
    }
}
