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
    private $_controllers = array();

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
        $this->_request_data['salesproject'] =& $this->_salesproject;

        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $this->_request_data['projects_url'] = $siteconfig->get_node_relative_url('org.openpsa.projects');
        $this->_request_data['invoices_url'] = $siteconfig->get_node_relative_url('org.openpsa.invoices');

        $this->_request_data['products'] = org_openpsa_products_product_dba::list_products();
    }

    /**
     * Helper that populates the toolbar
     */
    private function _populate_toolbar()
    {
        if ($this->_salesproject->can_do('midgard:update'))
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "salesproject/edit/{$this->_salesproject->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('edit'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'e',
                )
            );
        }

        /*if ($this->_salesproject->can_do('midgard:delete'))
        {
            $this->_view_toolbar->add_item(Array(
                MIDCOM_TOOLBAR_URL => "salesproject/delete/{$this->_salesproject->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('delete'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
            ));
        }*/

        $relatedto_button_settings = org_openpsa_relatedto_plugin::common_toolbar_buttons_defaults();
        $relatedto_button_settings['wikinote']['wikiword'] = sprintf($this->_l10n->get($this->_config->get('new_wikinote_wikiword_format')), $this->_request_data['salesproject']->title, date('Y-m-d H:i'));
        org_openpsa_relatedto_plugin::common_node_toolbar_buttons($this->_view_toolbar, $this->_request_data['salesproject'], $this->_component, $relatedto_button_settings);

        $_MIDCOM->bind_view_to_object($this->_salesproject);
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
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_view($handler_id, array $args, array &$data)
    {
        $this->_salesproject = new org_openpsa_sales_salesproject_dba($args[0]);

        $_MIDCOM->load_library('midcom.helper.datamanager2');

        $this->_load_controller();

        $this->_list_deliverables();

        $this->_prepare_request_data();

        $this->_populate_toolbar();

        $this->add_breadcrumb("salesproject/{$this->_salesproject->guid}/", $this->_salesproject->title);
        $_MIDCOM->set_26_request_metadata($this->_salesproject->metadata->revised, $this->_salesproject->guid);
        $_MIDCOM->set_pagetitle($this->_salesproject->title);

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.core/list.css");

        org_openpsa_invoices_viewer::add_head_elements_for_invoice_grid();
    }

    /**
     * Helper that lists all deliverables belonging to the current project
     */
    private function _list_deliverables()
    {
        $qb = org_openpsa_sales_salesproject_deliverable_dba::new_query_builder();
        $qb->add_constraint('salesproject', '=', $this->_salesproject->id);
        $qb->add_constraint('up', '=', 0);

        if ($this->_salesproject->status != org_openpsa_sales_salesproject_dba::STATUS_LOST)
        {
            $qb->add_constraint('state', '<>', org_openpsa_sales_salesproject_deliverable_dba::STATUS_DECLINED);
        }
        $qb->add_order('state');
        $qb->add_order('metadata.created', 'DESC');
        $deliverables = $qb->execute();
        foreach ($deliverables as $deliverable)
        {
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

        if (count($data['products']) > 0)
        {
            asort($data['products']);
            // We have products defined in the system, add deliverable support
            midcom_show_style('show-salesproject-deliverables-header');

            if (array_key_exists('deliverables_objects', $data))
            {
                foreach ($data['deliverables_objects'] as $deliverable)
                {
                    $data['deliverable'] = $this->_controllers[$deliverable->id]->get_content_html();
                    $data['deliverable_object'] =& $deliverable;
                    $data['deliverable_toolbar'] = $this->_build_deliverable_toolbar($deliverable);
                    try
                    {
                        $data['product'] = org_openpsa_products_product_dba::get_cached($deliverable->product);
                    }
                    catch (midcom_error $e)
                    {
                        $data['product'] = false;
                    }
                    if ($deliverable->orgOpenpsaObtype == ORG_OPENPSA_PRODUCTS_DELIVERY_SUBSCRIPTION)
                    {
                        midcom_show_style('show-salesproject-deliverables-subscription');
                    }
                    else
                    {
                        midcom_show_style('show-salesproject-deliverables-item');
                    }
                }
            }
            midcom_show_style('show-salesproject-deliverables-footer');
        }

        midcom_show_style('show-salesproject-related');
    }

    private function _build_deliverable_toolbar($deliverable)
    {
        $toolbar = '';

        if ($deliverable->state < org_openpsa_sales_salesproject_deliverable_dba::STATUS_DECLINED)
        {
            //new, proposed
            $toolbar .= "<input type=\"submit\" class=\"order\" name=\"order\" value=\"" . $this->_l10n->get('mark ordered') . "\" />\n";
            $toolbar .= "<input type=\"submit\" class=\"decline\" name=\"decline\" value=\"" . $this->_l10n->get('mark declined') . "\" />\n";
        }
        else if ($deliverable->state == org_openpsa_sales_salesproject_deliverable_dba::STATUS_DECLINED)
        {
            //declined, nothing to do...
        }
        else if ($deliverable->state < org_openpsa_sales_salesproject_deliverable_dba::STATUS_DELIVERED)
        {
            //started, ordered
            if (   $deliverable->orgOpenpsaObtype == ORG_OPENPSA_PRODUCTS_DELIVERY_SUBSCRIPTION)
            {
                $entries = $deliverable->get_at_entries();
                if (isset($entries[0]))
                {
                    $toolbar .= "<p>" . sprintf($this->_l10n->get('next invoice will be generated on %s'), strftime('%x', $entries[0]->start));
                    if (   $entries[0]->status == MIDCOM_SERVICES_AT_STATUS_SCHEDULED
                        && midcom::get('auth')->can_user_do('midgard:create', null, 'org_openpsa_invoices_invoice_dba'))
                    {
                        $toolbar .= ' <input type="hidden" name="at_entry" value="' . $entries[0]->guid . "\" />\n";
                        $toolbar .= ' <input type="submit" class="run_cycle" name="run_cycle" value="' . $this->_l10n->get('generate now') . "\" />\n";
                    }
                    $toolbar .= "</p>\n";
                }
            }
            else if ($deliverable->state == org_openpsa_sales_salesproject_deliverable_dba::STATUS_ORDERED)
            {
                $toolbar .= "<input type=\"submit\" class=\"deliver\" name=\"deliver\" value=\"" . $this->_l10n->get('mark delivered') . "\" />\n";
            }
        }
        else if ($deliverable->state == org_openpsa_sales_salesproject_deliverable_dba::STATUS_INVOICED)
        {
            //delivered, invoiced
            if ($deliverable->invoiced > 0)
            {
                $toolbar .= "<p>" . $this->_l10n->get('invoiced') . ': ' . org_openpsa_helpers::format_number($deliverable->invoiced, 2) . "</p>\n";
            }
        }
        else if (   $deliverable->orgOpenpsaObtype != ORG_OPENPSA_PRODUCTS_DELIVERY_SUBSCRIPTION
                 && midcom::get('auth')->can_user_do('midgard:create', null, 'org_openpsa_invoices_invoice_dba'))
        {
            //not invoiced yet
            $client_class = $this->_config->get('calculator');
            $client = new $client_class($deliverable);
            $client->run();

            if ($client->get_price() > 0)
            {
                $toolbar .= "<input type=\"submit\" class=\"invoice\" name=\"invoice\" value=\"" . sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('invoice')) . "\" />\n";
            }
        }

        return $toolbar;
    }
}
?>