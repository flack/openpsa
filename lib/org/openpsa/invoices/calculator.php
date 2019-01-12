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
    private $_deliverable;

    /**
     * The invoice we're working on
     *
     * @var org_openpsa_invoices_invoice_dba
     */
    private $_invoice;

    /**
     */
    public function process_deliverable(org_openpsa_sales_salesproject_deliverable_dba $deliverable, $cycle_number = null)
    {
        $this->_deliverable = $deliverable;
        // Recalculate price to catch possible unit changes
        $client_class = midcom_baseclasses_components_configuration::get('org.openpsa.sales', 'config')->get('calculator');
        $client = new $client_class();
        $client->run($this->_deliverable);

        $this_cycle_amount = $client->get_price();

        if ($this_cycle_amount == 0) {
            debug_add('Invoice sum 0, skipping invoice creation');
            return 0;
        }

        $this->_invoice = $this->_probe_invoice($cycle_number);

        if (!$this->_invoice->update()) {
            throw new midcom_error("The invoice could not be saved. Last Midgard error was: " . midcom_connection::get_error_string());
        }

        // TODO: Create invoicing task if assignee is defined

        $items = $client->get_invoice_items($this->_invoice);

        foreach ($items as $item) {
            $item->deliverable = $this->_deliverable->id;
            $item->skip_invoice_update = true;
            if ($item->id) {
                $stat = $item->update();
            } else {
                $stat = $item->create();
            }
            if (!$stat) {
                throw new midcom_error('Failed to save item to disk, ' . midcom_connection::get_error_string());
            }
        }
        org_openpsa_invoices_invoice_item_dba::update_invoice($this->_invoice);
        org_openpsa_invoices_invoice_item_dba::update_deliverable($this->_deliverable);

        return $this_cycle_amount;
    }

    /**
     * Invoice getter
     *
     * @return org_openpsa_invoices_invoice_dba
     */
    public function get_invoice()
    {
        return $this->_invoice;
    }

    /**
     * Invoice setter
     *
     * @param org_openpsa_invoices_invoice_dba $invoice
     */
    public function set_invoice(org_openpsa_invoices_invoice_dba $invoice)
    {
        $this->_invoice = $invoice;
    }

    /**
     * Try to locate unsent invoices for deliverables in the same salesproject
     *
     * Example use case: A support contract with multiple hourly rates (defined
     * as deliverables) for different types of work. Instead of sending the customer
     * one invoice per hourly rate per month, one composite invoice for all fees is generated
     */
    private function _probe_invoice($cycle_number)
    {
        $item_mc = org_openpsa_invoices_invoice_item_dba::new_collector('deliverable.salesproject', $this->_deliverable->salesproject);
        $item_mc->add_constraint('invoice.sent', '=', 0);
        $suspects = $item_mc->get_values('invoice');

        // validate suspects.. we want no cancelation invoices
        if (count($suspects) > 0) {
            $invoice_mc = org_openpsa_invoices_invoice_dba::new_collector();
            $invoice_mc->add_constraint('cancelationInvoice', 'IN', array_values($suspects));
            $cancelation_ids = $invoice_mc->get_values('cancelationInvoice');

            $suspects = array_diff($suspects, $cancelation_ids);
        }

        // check which suspects are left
        if (count($suspects) > 0) {
            return new org_openpsa_invoices_invoice_dba(array_pop($suspects));
        }
        // Nothing found, create a new invoice
        return $this->_create_invoice($cycle_number);
    }

    private function _create_invoice($cycle_number)
    {
        $salesproject = new org_openpsa_sales_salesproject_dba($this->_deliverable->salesproject);
        $invoice = new org_openpsa_invoices_invoice_dba();
        $invoice->customer = $salesproject->customer;
        $invoice->customerContact = $salesproject->customerContact;
        $invoice->number = $invoice->generate_invoice_number();
        $invoice->owner = $salesproject->owner;
        $invoice->vat = $invoice->get_default('vat');
        $invoice->description = $invoice->get_default('remarks');

        if (!$invoice->create()) {
            throw new midcom_error('Failed to create invoice, ' . midcom_connection::get_error_string());
        }
        // Register the cycle number for reporting purposes
        if (!is_null($cycle_number)) {
            $invoice->set_parameter('org.openpsa.sales', 'cycle_number', $cycle_number);
        }
        return $invoice;
    }
}
