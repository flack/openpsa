<?php
use Doctrine\ORM\Query\Expr\Join;

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
    /**
     * The deliverable we're processing
     *
     * @var org_openpsa_sales_salesproject_deliverable_dba
     */
    private $_deliverable;

    /**
     * The deliverable's cost
     *
     * @var float
     */
    private $_cost = 0;

    /**
     * The deliverable's price
     *
     * @var float
     */
    private $_price = 0;

    /**
     * Perform the cost/price calculation
     */
    public function run(org_openpsa_sales_salesproject_deliverable_dba $deliverable)
    {
        $this->_deliverable = $deliverable;
        $units = $this->get_units();
        $this->_price = $units * $this->_deliverable->pricePerUnit;

        // Count cost based on the cost type
        switch ($this->_deliverable->costType) {
            case '%':
                // The cost is a percentage of the price
                $this->_cost = $this->_price / 100 * $this->_deliverable->costPerUnit;
                break;
            default:
            case 'm':
                // The cost is a fixed sum per unit
                $this->_cost = $units * $this->_deliverable->costPerUnit;
                break;
        }
    }

    private function get_units()
    {
        if (   $this->_deliverable->invoiceByActualUnits
            || $this->_deliverable->plannedUnits == 0) {
            // In most cases we calculate the price based on the actual units entered
            return $this->_deliverable->units;
        }
        // But in some deals we use the planned units instead
        return $this->_deliverable->plannedUnits;
    }

    /**
     * Returns the calculated cost
     *
     * @return float cost value
     */
    public function get_cost()
    {
        return $this->_cost;
    }

    /**
     * Returns the calculated price
     *
     * @return float price value
     */
    public function get_price()
    {
        return $this->_price;
    }

    /**
     * Returns the invoice items that should be written
     *
     * @return org_openpsa_invoices_invoice_item_dba[]
     */
    public function get_invoice_items(org_openpsa_invoices_invoice_dba $invoice)
    {
        $this->_invoice = $invoice;

        $items = [];
        // Mark the tasks (and hour reports) related to this agreement as invoiced
        $tasks = $this->_find_tasks();

        foreach ($tasks as $task) {
            $hours_marked = org_openpsa_projects_workflow::mark_invoiced($task, $invoice);

            $items[] = $this->_generate_invoice_item($task->title, $hours_marked, $task);
        }

        if (sizeof($tasks) == 0) {
            $items[] = $this->_generate_invoice_item($this->_deliverable->title, $this->_deliverable->units);
        }
        return $items;
    }

    private function _generate_invoice_item($description, $units, org_openpsa_projects_task_dba $task = null)
    {
        $item = new org_openpsa_invoices_invoice_item_dba();
        $item->description = $description;
        $item->invoice = $this->_invoice->id;
        $item->pricePerUnit = $this->_deliverable->pricePerUnit;
        $item->units = $this->get_units();

        if (null !== $task) {
            $item->task = $task->id;
        }

        return $item;
    }

    private function _find_tasks()
    {
        $qb = org_openpsa_projects_task_dba::new_query_builder();

        if ($this->_deliverable->invoiceByActualUnits) {
            $qb->add_constraint('invoiceableHours', '>', 0);
        } else {
            $qb->get_doctrine()
                ->leftJoin('org_openpsa_invoice_item', 'i', Join::WITH, 'i.task = c.id')
                ->where('i.deliverable IS NULL');
        }
        $qb->add_constraint('agreement', '=', $this->_deliverable->id);
        $qb->execute();
        return $qb->execute();
    }

    /**
     * Returns identifier number for next invoice
     *
     * @return int invoice number
     */
    public function generate_invoice_number()
    {
        $qb = org_openpsa_invoices_invoice_dba::new_query_builder();
        $qb->add_order('number', 'DESC');
        $qb->set_limit(1);
        midcom::get()->auth->request_sudo('org.openpsa.invoices');
        $last_invoice = $qb->execute_unchecked();
        midcom::get()->auth->drop_sudo();

        if (count($last_invoice) == 0) {
            $previous = 0;
        } else {
            $previous = $last_invoice[0]->number;
        }

        return $previous + 1;
    }
}
