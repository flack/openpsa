<?php
/**
 * @package org.openpsa.expenses
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Doctrine\ORM\Query\Expr\Join;

/**
 * Hour report based cost/price calculator
 *
 * @package org.openpsa.expenses
 */
class org_openpsa_expenses_calculator extends org_openpsa_sales_calculator_default
{
    /**
     * @inheritdoc
     */
    public function get_invoice_items(org_openpsa_invoices_invoice_dba $invoice) : array
    {
        // Mark the tasks (and hour reports) related to this agreement as invoiced
        $qb = org_openpsa_projects_task_dba::new_query_builder();
        $qb->add_constraint('agreement', '=', $this->deliverable->id);
        $tasks = $this->_find_tasks($qb);

        if (empty($tasks)) {
            return parent::get_invoice_items($invoice);
        }

        $items = [];
        foreach ($tasks as $task) {
            $hours_marked = org_openpsa_expenses_hour_report_dba::mark_invoiced($task, $invoice);
            $items[] = $this->generate_invoice_item($task->title, $hours_marked, $invoice->id, $task);

            $qb = org_openpsa_projects_task_dba::new_query_builder();
            $qb->add_constraint('up', 'INTREE', $task->id);
            foreach ($this->_find_tasks($qb) as $subtask) {
                $hours_marked = org_openpsa_expenses_hour_report_dba::mark_invoiced($subtask, $invoice);
                $items[] = $this->generate_invoice_item($subtask->title, $hours_marked, $invoice->id, $subtask);
            }
        }

        return $items;
    }

    private function _find_tasks(midcom_core_querybuilder $qb) : array
    {
        if ($this->deliverable->invoiceByActualUnits) {
            $qb->add_constraint('invoiceableHours', '>', 0);
        } else {
            $qb->get_doctrine()
                ->leftJoin('org_openpsa_invoice_item', 'i', Join::WITH, 'i.task = c.id')
                ->where('i.deliverable IS NULL');
        }
        return $qb->execute();
    }
}
