<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class org_openpsa_expenses_calculatorTest extends openpsa_testcase
{
    public function testGet_invoice_items()
    {
        $salesproject = $this->create_object(org_openpsa_sales_salesproject_dba::class);
        $this->_deliverable = $this->create_object(org_openpsa_sales_salesproject_deliverable_dba::class, ['salesproject' => $salesproject->id]);
        $project = $this->create_object(org_openpsa_projects_project::class);
        $task_attributes = [
            'project' => $project->id,
            'agreement' => $this->_deliverable->id
        ];
        $task = $this->create_object(org_openpsa_projects_task_dba::class, $task_attributes);
        $invoice = $this->create_object(org_openpsa_invoices_invoice_dba::class);

        midcom::get()->auth->request_sudo('org.openpsa.sales');

        $calculator = new org_openpsa_expenses_calculator;
        $calculator->run($this->_deliverable);
        $items = $calculator->get_invoice_items($invoice);

        $this->assertCount(1, $items);
        $this->assertEquals($task->id, $items[0]->task);

        midcom::get()->auth->drop_sudo();
    }
}
