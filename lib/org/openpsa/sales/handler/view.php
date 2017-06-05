<?php
/**
 * @package org.openpsa.sales
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Salesproject display class
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_handler_view extends midcom_baseclasses_components_handler
{
    private $_controllers = [];

    /**
     * The salesproject to display
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
        $this->_request_data['salesproject'] = $this->_salesproject;

        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $this->_request_data['projects_url'] = $siteconfig->get_node_relative_url('org.openpsa.projects');

        $this->_request_data['products'] = $this->_list_products();
    }

    private function _list_products()
    {
        $mc = org_openpsa_products_product_dba::new_collector();

        $mc->add_order('productGroup');
        $mc->add_order('code');
        $mc->add_order('title');
        $mc->add_constraint('start', '<=', time());
        $mc->begin_group('OR');
            /*
             * List products that either have no defined end-of-market dates
             * or are still in market
             */
            $mc->add_constraint('end', '=', 0);
            $mc->add_constraint('end', '>=', time());
        $mc->end_group();

        return $mc->get_rows(['code', 'title', 'delivery', 'price', 'unit', 'productGroup'], 'id');
    }

    /**
     * Populate the toolbar
     */
    private function _populate_toolbar()
    {
        $buttons = [];
        if ($this->_salesproject->can_do('midgard:update')) {
            $workflow = $this->get_workflow('datamanager2');
            $buttons[] = $workflow->get_button("salesproject/edit/{$this->_salesproject->guid}/", [
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            ]);
        }

        if ($this->_salesproject->can_do('midgard:delete')) {
            $workflow = $this->get_workflow('delete', ['object' => $this->_salesproject, 'recursive' => true]);
            $buttons[] = $workflow->get_button("salesproject/delete/{$this->_salesproject->guid}/");
        }

        if (!empty($this->_request_data['projects_url'])) {
            $prefix = midcom_connection::get_url('self') . $this->_request_data['projects_url'];
            $buttons[] = [
                MIDCOM_TOOLBAR_URL => $prefix . "project/{$this->_salesproject->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_i18n->get_string('org.openpsa.projects', 'org.openpsa.projects'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/jump-to.png',
            ];
        }

        if (   $this->_config->get('sales_pdfbuilder_class')
            && $this->_salesproject->can_do('midgard:update')
            && $this->is_pdf_creatable()) {
            $workflow = $this->get_workflow('datamanager2');
            $buttons[] = $workflow->get_button("salesproject/render/{$this->_salesproject->guid}/", [
                MIDCOM_TOOLBAR_ACCESSKEY => 'p',
                MIDCOM_TOOLBAR_ICON => 'stock-icons/32x32/PDF.png',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create_pdf'),
            ]);
        }

        $this->_view_toolbar->add_items($buttons);

        $relatedto_button_settings = org_openpsa_relatedto_plugin::common_toolbar_buttons_defaults();
        $formatter = $this->_l10n->get_formatter();
        $relatedto_button_settings['wikinote']['wikiword'] = str_replace('/', '-', sprintf($this->_l10n->get($this->_config->get('new_wikinote_wikiword_format')), $this->_salesproject->title, $formatter->datetime()));
        unset($relatedto_button_settings['task']);
        org_openpsa_relatedto_plugin::common_node_toolbar_buttons($this->_view_toolbar, $this->_salesproject, $this->_component, $relatedto_button_settings);

        $this->bind_view_to_object($this->_salesproject);
    }

    private function is_pdf_creatable()
    {
        if ($this->_salesproject->state != org_openpsa_sales_salesproject_dba::STATE_LOST) {
            $qb = org_openpsa_sales_salesproject_deliverable_dba::new_query_builder();
            $qb->add_constraint('salesproject', '=', $this->_salesproject->id);
            $qb->add_constraint('up', '=', 0);
            $qb->add_constraint('state', '<', org_openpsa_sales_salesproject_deliverable_dba::STATE_DECLINED);
            return $qb->count() > 0;
        }
        return false;
    }

    private function _load_controller()
    {
        $this->_request_data['schemadb_salesproject'] = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_salesproject'));
        $this->_request_data['schemadb_salesproject_deliverable'] = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_deliverable'));

        $this->_request_data['controller'] = midcom_helper_datamanager2_controller::create('ajax');
        $this->_request_data['controller']->schemadb =& $this->_request_data['schemadb_salesproject'];
        $this->_request_data['controller']->set_storage($this->_salesproject);
        $this->_request_data['controller']->process_ajax();
    }

    /**
     * Looks up a salesproject to display.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_view($handler_id, array $args, array &$data)
    {
        $this->_salesproject = new org_openpsa_sales_salesproject_dba($args[0]);

        $this->_load_controller();
        $this->_list_deliverables();
        $this->_prepare_request_data();
        $this->_populate_toolbar();

        if ($customer = $this->_salesproject->get_customer()) {
            $this->add_breadcrumb("list/customer/{$customer->guid}/", $customer->get_label());
        }

        $this->add_breadcrumb("salesproject/{$this->_salesproject->guid}/", $this->_salesproject->title);
        midcom::get()->metadata->set_request_metadata($this->_salesproject->metadata->revised, $this->_salesproject->guid);
        midcom::get()->head->set_pagetitle($this->_salesproject->title);

        midcom_helper_datamanager2_widget_autocomplete::add_head_elements();
        org_openpsa_invoices_viewer::add_head_elements_for_invoice_grid();

        midcom::get()->head->add_jsfile(MIDCOM_JQUERY_UI_URL . '/widgets/button.min.js');
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/' . $this->_component . '/sales.js');

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.core/list.css");
    }

    /**
     * List all deliverables belonging to the current project
     */
    private function _list_deliverables()
    {
        $qb = org_openpsa_sales_salesproject_deliverable_dba::new_query_builder();
        $qb->add_constraint('salesproject', '=', $this->_salesproject->id);
        $qb->add_constraint('up', '=', 0);

        if ($this->_salesproject->state != org_openpsa_sales_salesproject_dba::STATE_LOST) {
            $qb->add_constraint('state', '<>', org_openpsa_sales_salesproject_deliverable_dba::STATE_DECLINED);
        }
        $qb->add_order('state');
        $qb->add_order('metadata.created', 'DESC');
        $deliverables = $qb->execute();
        $this->_request_data['deliverables_objects'] = [];
        foreach ($deliverables as $deliverable) {
            $this->_controllers[$deliverable->id] = midcom_helper_datamanager2_controller::create('ajax');
            $this->_controllers[$deliverable->id]->schemadb =& $this->_request_data['schemadb_salesproject_deliverable'];
            $this->_controllers[$deliverable->id]->set_storage($deliverable);
            $this->_controllers[$deliverable->id]->process_ajax();
            $this->_request_data['deliverables_objects'][$deliverable->guid] = $deliverable;
        }
    }

    /**
     * Shows the loaded salesproject.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_view($handler_id, array &$data)
    {
        // For AJAX handling it is the controller that renders everything
        $data['view_salesproject'] = $data['controller']->get_content_html();
        midcom_show_style('show-salesproject');
        midcom_show_style('show-salesproject-deliverables-header');

        foreach ($data['deliverables_objects'] as $deliverable) {
            $data['deliverable'] = $this->_controllers[$deliverable->id]->get_content_html();
            $data['deliverable_object'] = $deliverable;
            $data['deliverable_toolbar'] = $this->build_status_toolbar($deliverable);
            if ($deliverable->orgOpenpsaObtype == org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION) {
                midcom_show_style('show-salesproject-deliverables-subscription');
            } else {
                midcom_show_style('show-salesproject-deliverables-item');
            }
        }
        midcom_show_style('show-salesproject-deliverables-footer');
    }

    private function build_status_toolbar(org_openpsa_sales_salesproject_deliverable_dba $deliverable)
    {
        $toolbar = ['label' => '', 'buttons' => []];
        $formatter = $this->_l10n->get_formatter();
        if ($deliverable->state < org_openpsa_sales_salesproject_deliverable_dba::STATE_DECLINED) {
            //new, proposed
            $toolbar['buttons']['order'] = $this->_l10n->get('mark ordered');
            $toolbar['buttons']['decline'] = $this->_l10n->get('mark declined');
        } elseif ($deliverable->state == org_openpsa_sales_salesproject_deliverable_dba::STATE_DECLINED) {
            //declined, nothing to do...
            return $toolbar;
        } elseif ($deliverable->state < org_openpsa_sales_salesproject_deliverable_dba::STATE_DELIVERED) {
            //started, ordered
            if ($deliverable->orgOpenpsaObtype == org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION) {
                if ($entries = $deliverable->get_at_entries()) {
                    $toolbar['label'] = sprintf($this->_l10n->get('next invoice will be generated on %s'), $formatter->date($entries[0]->start));
                    if (   $entries[0]->status == midcom_services_at_entry_dba::SCHEDULED
                        && midcom::get()->auth->can_user_do('midgard:create', null, 'org_openpsa_invoices_invoice_dba')) {
                        $toolbar['buttons']['run_cycle'] = $this->_l10n->get('generate now');
                    }
                }
            } elseif ($deliverable->state == org_openpsa_sales_salesproject_deliverable_dba::STATE_ORDERED) {
                $toolbar['buttons']['deliver'] = $this->_l10n->get('mark delivered');
            }
        } elseif ($deliverable->state == org_openpsa_sales_salesproject_deliverable_dba::STATE_INVOICED) {
            //delivered, invoiced
            if ($deliverable->invoiced > 0) {
                $toolbar['label'] = $this->_l10n->get('invoiced') . ': ' . $formatter->number($deliverable->invoiced);
            }
        } elseif (   $deliverable->orgOpenpsaObtype != org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION
                 && midcom::get()->auth->can_user_do('midgard:create', null, 'org_openpsa_invoices_invoice_dba')) {
            //not invoiced yet
            $client_class = $this->_config->get('calculator');
            $client = new $client_class();
            $client->run($deliverable);

            if ($client->get_price() > 0) {
                $toolbar['buttons']['invoice'] = sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('invoice'));
            }
        }
        return $toolbar;
    }
}
