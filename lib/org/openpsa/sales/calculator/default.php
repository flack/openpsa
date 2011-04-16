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
     * Constructor
     */
    public function __construct(org_openpsa_sales_salesproject_deliverable_dba $deliverable)
    {
        $this->_deliverable = $deliverable;
    }

    /**
     * Perform the cost/price calculation
     */
    public function run()
    {
        if ($this->_deliverable->id)
        {
            // Check if we have subcomponents
            $deliverables = $this->_deliverable->get_components();
            if (count($deliverables) > 0)
            {
                // If subcomponents exist, the price and cost per unit default to the
                // sum of price and cost of all subcomponents
                $pricePerUnit = 0;
                $costPerUnit = 0;

                foreach ($deliverables as $deliverable)
                {
                    $pricePerUnit = $pricePerUnit + $deliverable->price;
                    $costPerUnit = $costPerUnit + $deliverable->cost;
                }

                $this->_deliverable->pricePerUnit = $pricePerUnit;
                $this->_deliverable->costPerUnit = $costPerUnit;

                // We can't have percentage-based cost type if the agreement
                // has subcomponents
                $this->_deliverable->costType = 'm';
            }
        }

        if (   $this->_deliverable->invoiceByActualUnits
            || $this->_deliverable->plannedUnits == 0)
        {
            // In most cases we calculate the price based on the actual units entered
            $this->_price = $this->_deliverable->units * $this->_deliverable->pricePerUnit;
        }
        else
        {
            // But in some deals we use the planned units instead
            $this->_price = $this->_deliverable->plannedUnits * $this->_deliverable->pricePerUnit;
        }

        // Count cost based on the cost type
        switch ($this->_deliverable->costType)
        {
            case '%':
                // The cost is a percentage of the price
                $this->_cost = $this->_price / 100 * $this->_deliverable->costPerUnit;
                break;
            default:
            case 'm':
                // The cost is a fixed sum per unit
                $this->_cost = $this->_deliverable->units * $this->_deliverable->costPerUnit;
                break;
        }
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
     * @return array
     */
    public function get_invoice_items(org_openpsa_invoices_invoice_dba $invoice)
    {
        $this->_invoice = $invoice;

        $items = array();
        // Mark the tasks (and hour reports) related to this agreement as invoiced
        $tasks = $this->_find_tasks();

        foreach ($tasks as $task)
        {
            $hours_marked = org_openpsa_projects_workflow::mark_invoiced($task, $invoice);

            $items[] = $this->_generate_invoice_item($task->title, $hours_marked);
        }

        if (sizeof($tasks) == 0)
        {
            $items[] = $this->_generate_invoice_item($this->_deliverable->title, $this->_deliverable->units);
        }
        return $items;
    }

    private function _generate_invoice_item($description, $units)
    {
        $item = new org_openpsa_invoices_invoice_item_dba();
        $item->description = $description;
        $item->invoice = $this->_invoice->id;
        $item->pricePerUnit = $this->_deliverable->pricePerUnit;

        if (   $this->_deliverable->invoiceByActualUnits
            || $this->_deliverable->plannedUnits == 0)
        {
            $item->units = $units;
        }
        else
        {
            $item->units = $this->_deliverable->plannedUnits;
        }

        return $item;
    }

    private function _find_tasks()
    {
        $qb = org_openpsa_projects_task_dba::new_query_builder();
        $qb->add_constraint('agreement', '=', $this->_deliverable->id);

        if ($this->_deliverable->invoiceByActualUnits)
        {
            $qb->add_constraint('invoiceableHours', '>', 0);
        }
        else
        {
            $skip_ids = array();
            $item_mc = org_openpsa_invoices_invoice_item::new_collector('deliverable', $this->_deliverable->id);
            $item_mc->add_value_property('task');
            $item_mc->execute();

            foreach ($item_mc->list_keys() as $guid => $empty)
            {
                $skip_ids[] = $item_mc->get_subkey($guid, 'task');
            }
            if (sizeof($skip_ids) > 0)
            {
                $qb->add_constraint('id', 'NOT IN', $skip_ids);
            }
        }
        return $qb->execute();
    }
}
?>