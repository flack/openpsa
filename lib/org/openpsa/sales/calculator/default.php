<?php
/**
 * @package org.openpsa.sales
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Default deliverable cost/price calculator
 *
 * @package org.openpsa.sales
 */
class org_openpsa_sales_calculator_default implements org_openpsa_invoices_interfaces_calculator
{
    protected org_openpsa_sales_salesproject_deliverable_dba $deliverable;

    private float $_cost = 0;

    private float $_price = 0;

    /**
     * Perform the cost/price calculation
     */
    public function run(org_openpsa_sales_salesproject_deliverable_dba $deliverable)
    {
        $this->deliverable = $deliverable;
        $units = $this->get_units();
        $this->_price = $units * $this->deliverable->pricePerUnit;

        // Count cost based on the cost type
        if ($this->deliverable->costType == '%') {
            // The cost is a percentage of the price
            $this->_cost = $this->_price / 100 * $this->deliverable->costPerUnit;
        } else {
            // The cost is a fixed sum per unit
            $this->_cost = $units * $this->deliverable->costPerUnit;
        }
    }

    private function get_units($units = false) : float
    {
        if (   $this->deliverable->invoiceByActualUnits
            || $this->deliverable->plannedUnits == 0) {
            // In most cases we calculate the price based on the actual units entered
            return $units ?: $this->deliverable->units;
        }
        // But in some deals we use the planned units instead
        return $this->deliverable->plannedUnits;
    }

    /**
     * @inheritdoc
     */
    public function get_cost() : float
    {
        return $this->_cost;
    }

    /**
     * @inheritdoc
     */
    public function get_price() : float
    {
        return $this->_price;
    }

    /**
     * Returns the invoice items that should be written
     *
     * @return org_openpsa_invoices_invoice_item_dba[]
     */
    public function get_invoice_items(org_openpsa_invoices_invoice_dba $invoice) : array
    {
        $description = $this->deliverable->get_cycle_identifier(time());
        return [$this->generate_invoice_item($description, $this->deliverable->units, $invoice->id)];
    }

    protected function generate_invoice_item(string $description, float $units, int $invoice, org_openpsa_projects_task_dba $task = null) : org_openpsa_invoices_invoice_item_dba
    {
        $item = new org_openpsa_invoices_invoice_item_dba();
        $item->description = $description;
        $item->invoice = $invoice;
        $item->pricePerUnit = $this->deliverable->pricePerUnit;
        $item->units = $this->get_units($units);

        if (null !== $task) {
            $item->task = $task->id;
        }

        return $item;
    }

    /**
     * @inheritdoc
     */
    public function generate_invoice_number() : int
    {
        $qb = org_openpsa_invoices_invoice_dba::new_query_builder();
        $qb->add_order('number', 'DESC');
        $qb->set_limit(1);
        midcom::get()->auth->request_sudo('org.openpsa.invoices');
        $last_invoice = $qb->execute_unchecked();
        midcom::get()->auth->drop_sudo();

        if (empty($last_invoice)) {
            $previous = 0;
        } else {
            $previous = $last_invoice[0]->number;
        }

        return $previous + 1;
    }
}
