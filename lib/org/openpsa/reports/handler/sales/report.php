<?php
/**
 * @package org.openpsa.reports
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Deliverable reports
 *
 * @package org.openpsa.reports
 */
class org_openpsa_reports_handler_sales_report extends org_openpsa_reports_handler_base
{
    public function _on_initialize()
    {
        $this->module = 'sales';
        $this->_initialize_datamanager();
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_generator($handler_id, array $args, array &$data)
    {
        $_MIDCOM->auth->require_valid_user();

        $this->_generator_load_redirect($args);
        $this->_handler_generator_style();

        $data['invoices'] = Array();

        // Calculate time range
        $data['start'] = $this->_request_data['query_data']['start'];
        $data['end'] = $this->_request_data['query_data']['end'];

        // List sales projects
        $salesproject_qb = org_openpsa_sales_salesproject_dba::new_query_builder();
        $salesproject_qb->add_constraint('status', '<>', org_openpsa_sales_salesproject_dba::STATUS_LOST);

        if ($this->_request_data['query_data']['resource'] != 'all')
        {
            $this->_request_data['query_data']['resource_expanded'] = $this->_expand_resource($this->_request_data['query_data']['resource']);
            $salesproject_qb->begin_group('OR');
            foreach ($this->_request_data['query_data']['resource_expanded'] as $pid)
            {
                $salesproject_qb->add_constraint('owner', '=', $pid);
            }
            $salesproject_qb->end_group();
        }
        $salesprojects = $salesproject_qb->execute();

        // List deliverables related to the sales projects
        $deliverable_qb = org_openpsa_sales_salesproject_deliverable_dba::new_query_builder();
        $deliverable_qb->add_constraint('state', '<>', 'org_openpsa_sales_salesproject_deliverable_dba::STATUS_DECLINED');
        $deliverable_qb->begin_group('OR');
        foreach ($salesprojects as $salesproject)
        {
            $deliverable_qb->add_constraint('salesproject', '=', $salesproject->id);
        }
        $deliverable_qb->end_group();
        $deliverables = $deliverable_qb->execute();

        $deliverable_guids = array();
        foreach ($deliverables as $deliverable)
        {
            $deliverable_guids[] = $deliverable->guid;
            $data['invoices'][$deliverable->guid] = array();
        }

        // List relations of invoices to the deliverables we have
        $mc = new org_openpsa_relatedto_collector($deliverable_guids, 'org_openpsa_invoices_invoice_dba');

        $mc->add_object_constraint('metadata.created', '>=', strftime('%Y-%m-%d %T', $data['start']));
        $mc->add_object_constraint('metadata.created', '<', strftime('%Y-%m-%d %T', $data['end']));

        // Get invoices our deliverables are related to
        $data['invoices'] = $mc->get_related_objects_grouped_by('toGuid');
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.core/list.css");
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_generator($handler_id, array &$data)
    {
        midcom_show_style('sales_report-deliverable-start');

        // Quick workaround to Bergies lazy determination of whether this is user's or everyone's report...
        if ($this->_request_data['query_data']['resource'] == 'user:' . $_MIDCOM->auth->user->guid)
        {
            // My report
            $data['handler_id'] = 'deliverable_report';
        }
        else
        {
            // Generic report
            $data['handler_id'] = 'sales_report';
        }
        /*** Copied from sales/handler/deliverable/report.php ***/
        midcom_show_style('sales_report-deliverable-header');

        $invoices_node = midcom_helper_misc::find_node_by_component('org.openpsa.invoices');

        $sums_per_person = Array();
        $sums_all = Array
        (
            'price'  => 0,
            'cost'   => 0,
            'profit' => 0,
        );
        $odd = true;
        foreach ($data['invoices'] as $deliverable_guid => $invoices)
        {
            if (count($invoices) == 0)
            {
                // No invoices sent in this project, skip
                continue;
            }

            try
            {
                $deliverable = org_openpsa_sales_salesproject_deliverable_dba::get_cached($deliverable_guid);
                $product = org_openpsa_products_product_dba::get_cached($deliverable->product);
                $salesproject = org_openpsa_sales_salesproject_dba::get_cached($deliverable->salesproject);
                $customer = midcom_db_group::get_cached($salesproject->customer);
            }
            catch (midcom_error $e)
            {
                continue;
            }
            if (!array_key_exists($salesproject->owner, $sums_per_person))
            {
                $sums_per_person[$salesproject->owner] = Array
                (
                    'price'  => 0,
                    'cost'   => 0,
                    'profit' => 0,
                );
            }

            // Calculate the price and cost from invoices
            $invoice_price = 0;
            $data['invoice_string'] = '';
            $invoice_cycle_numbers = Array();
            foreach ($invoices as $invoice)
            {
                $invoice_price += $invoice->sum;
                $invoice_class = $invoice->get_invoice_class();

                if ($invoices_node)
                {
                    $invoice_label = "<a class=\"{$invoice_class}\" href=\"{$invoices_node[MIDCOM_NAV_FULLURL]}invoice/{$invoice->guid}/\">" . $invoice->get_label() . "</a>";
                }
                else
                {
                    $invoice_label = $invoice->get_label();
                }

                if ($product->delivery == ORG_OPENPSA_PRODUCTS_DELIVERY_SUBSCRIPTION)
                {
                    $invoice_cycle_numbers[] = (int) $invoice->parameter('org.openpsa.sales', 'cycle_number');
                }

                $data['invoice_string'] .= "<li class=\"{$invoice_class}\">{$invoice_label}</li>\n";
            }

            if ($product->delivery == ORG_OPENPSA_PRODUCTS_DELIVERY_SUBSCRIPTION)
            {
                // This is a subscription, it should be shown only if it is the first invoice
                if (!in_array(1, $invoice_cycle_numbers))
                {
                    continue;
                    // This will skip to next deliverable
                }

                $scheduler = new org_openpsa_invoices_scheduler($deliverable);

                if ($deliverable->end == 0)
                {
                    // Subscription doesn't have an end date, use specified amount of months for calculation
                    $cycles = $scheduler->calculate_cycles($this->_config->get('subscription_profit_months'));
                    $data['calculation_basis'] = sprintf($data['l10n']->get('%s cycles in %s months'), $cycles, $this->_config->get('subscription_profit_months'));
                }
                else
                {
                    $cycles = $scheduler->calculate_cycles();
                    $data['calculation_basis'] = sprintf($data['l10n']->get('%s cycles, %s - %s'), $cycles, strftime('%x', $deliverable->start), strftime('%x', $deliverable->end));
                }

                $price = $deliverable->price * $cycles;
                $cost = $deliverable->cost * $cycles;
            }
            else
            {
                // This is a single delivery, calculate cost as percentage as it may be invoiced in pieces
                if ($deliverable->price)
                {
                    $cost_percentage = 100 / $deliverable->price * $invoice_price;
                    $cost = $deliverable->cost / 100 * $cost_percentage;
                }
                else
                {
                    $cost_percentage = 100;
                    $cost = $deliverable->cost;
                }
                $price = $invoice_price;
                $data['calculation_basis'] = sprintf($data['l10n']->get('%s%% of %s'), round($cost_percentage), $deliverable->price);
            }

            // And now just count the profit
            $profit = $price - $cost;
            $data['customer'] = $customer;
            $data['salesproject'] = $salesproject;
            $data['deliverable'] = $deliverable;

            $data['price'] = $price;
            $sums_per_person[$salesproject->owner]['price'] += $price;
            $sums_all['price'] += $price;

            $data['cost'] = $cost;
            $sums_per_person[$salesproject->owner]['cost'] += $cost;
            $sums_all['cost'] += $cost;

            $data['profit'] = $profit;
            $sums_per_person[$salesproject->owner]['profit'] += $profit;
            $sums_all['profit'] += $profit;

            if ($odd)
            {
                $data['row_class'] = '';
                $odd = false;
            }
            else
            {
                $data['row_class'] = ' class="even"';
                $odd = true;
            }

            midcom_show_style('sales_report-deliverable-item');
        }

        $data['sums_per_person'] = $sums_per_person;
        $data['sums_all'] = $sums_all;
        midcom_show_style('sales_report-deliverable-footer');
        /*** /Copied from sales/handler/deliverable/report.php ***/
        midcom_show_style('sales_report-deliverable-end');
    }
}
?>