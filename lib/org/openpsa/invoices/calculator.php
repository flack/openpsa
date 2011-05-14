<?php
/**
 * @package org.openpsa.invoices
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Helper class for deliverable invoicing
 *
 * @package org.openpsa.invoices
 */
class org_openpsa_invoices_calculator extends midcom_baseclasses_components_purecode
{
    /**
     * The deliverable we're processing
     *
     * @var org_openpsa_sales_salesproject_deliverable_dba
     */
    private $_deliverable = null;

    /**
     * The invoice we're working on
     *
     * @var org_openpsa_invoices_invoice_dba
     */
    private $_invoice = null;

    /**
     */
    public function process_deliverable(org_openpsa_sales_salesproject_deliverable_dba $deliverable, $cycle_number = null)
    {
        $this->_deliverable = $deliverable;
        // Recalculate price to catch possible unit changes
        $client_class = midcom_baseclasses_components_configuration::get('org.openpsa.sales', 'config')->get('calculator');
        $client = new $client_class($this->_deliverable);
        $client->run();

        $this_cycle_amount = $client->get_price();

        $this->_deliverable->invoiced = $this->_deliverable->invoiced + $this_cycle_amount;
        $this->_deliverable->update();

        if ($this_cycle_amount == 0)
        {
            debug_add('Invoice sum 0, skipping invoice creation');
            return 0;
        }

        $this->_invoice = $this->_probe_invoice($cycle_number);
        $this->_invoice->due = ($this->_invoice->get_default_due() * 3600 * 24) + time();

        if (!$this->_invoice->update())
        {
            throw new midcom_error("The invoice could not be saved. Last Midgard error was: " . midcom_connection::get_error_string());
        }

        // TODO: Create invoicing task if assignee is defined

        $items = $client->get_invoice_items($this->_invoice);

        foreach ($items as $item)
        {
            $item->deliverable = $this->_deliverable->id;
            $item->skip_invoice_update = true;
            if ($item->id)
            {
                $stat = $item->update();
            }
            else
            {
                $stat = $item->create();
            }
            if (!$stat)
            {
                throw new midcom_error('Failed to save item to disk, ' . midcom_connection::get_error_string());
            }
        }
        $this->_invoice->sum = $this->_invoice->get_invoice_sum();
        $this->_invoice->update();

        return $this_cycle_amount;
    }

    public function set_invoice(org_openpsa_invoices_invoice_dba $invoice)
    {
        $this->_invoice = $invoice;
    }

    /**
     * Helper function that tries to locate unsent invoices for deliverables in the same salesproject
     *
     * Example use case: A support contract with multiple hourly rates (defined
     * as deliverables) for different types of work. Instead of sending the customer
     * one invoice per hourly rate per month, one composite invoice for all fees is generated
     */
    private function _probe_invoice($cycle_number)
    {
        if (is_null($cycle_number))
        {
            //we're not working with a subscription, so better create a new invoice right away
            return $this->_create_invoice();
        }
        $deliverable_mc = org_openpsa_sales_salesproject_deliverable_dba::new_collector('salesproject', $this->_deliverable->salesproject);
        $deliverable_mc->add_constraint('state', '>', ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_DECLINED);
        $deliverable_mc->add_constraint('product.delivery', '=', ORG_OPENPSA_PRODUCTS_DELIVERY_SUBSCRIPTION);
        $deliverables = $deliverable_mc->get_values('id');

        $item_mc = org_openpsa_invoices_invoice_item_dba::new_collector('metadata.deleted', false);
        $item_mc->add_constraint('deliverable', 'IN', $deliverables);
        $suspects = $item_mc->get_values('invoice');

        if (sizeof($suspects) > 0)
        {
            $qb = org_openpsa_invoices_invoice_dba::new_query_builder();
            $qb->add_constraint('id', 'IN', $suspects);
            $qb->add_constraint('parameter.value', '=', $cycle_number);
            $qb->add_constraint('sent', '=', 0);
            $results = $qb->execute();
            if (sizeof($results) == 1)
            {
                return $results[0];
            }
        }
        //Nothing or ambiguous results found, create a new invoice
        return $this->_create_invoice($cycle_number);
    }

    private function _create_invoice($cycle_number = null)
    {
        $salesproject = org_openpsa_sales_salesproject_dba::get_cached($this->_deliverable->salesproject);
        $invoice = new org_openpsa_invoices_invoice_dba();
        $invoice->customer = $salesproject->customer;
        $invoice->number = org_openpsa_invoices_invoice_dba::generate_invoice_number();
        $invoice->owner = $salesproject->owner;

        $invoice->vat = $invoice->get_default_vat();
        $invoice->due = ($invoice->get_default_due() * 3600 * 24) + time();

        if ($invoice->create())
        {
            // Register the cycle number for reporting purposes
            if (!is_null($cycle_number))
            {
                $invoice->parameter('org.openpsa.sales', 'cycle_number', $cycle_number);
            }
            return $invoice;
        }
        else
        {
            throw new midcom_error('Failed to create invoice, ' . midcom_connection::get_error_string());
        }
    }
}
?>