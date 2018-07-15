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
class org_openpsa_sales_calculatorTest extends openpsa_testcase
{
    protected $_deliverable;

    public function setUp()
    {
        $salesproject = $this->create_object(org_openpsa_sales_salesproject_dba::class);
        $this->_deliverable = $this->create_object(org_openpsa_sales_salesproject_deliverable_dba::class, ['salesproject' => $salesproject->id]);
    }

    public function testGet_invoice_items()
    {
        midcom::get()->auth->request_sudo('org.openpsa.sales');

        $project = $this->create_object(org_openpsa_projects_project::class);
        $task_attributes = [
            'project' => $project->id,
            'agreement' => $this->_deliverable->id
        ];
        $task = $this->create_object(org_openpsa_projects_task_dba::class, $task_attributes);
        $invoice = $this->create_object(org_openpsa_invoices_invoice_dba::class);

        $calculator = new org_openpsa_sales_calculator_default();
        $calculator->run($this->_deliverable);
        $items = $calculator->get_invoice_items($invoice);

        $this->assertCount(1, $items);
        $this->assertEquals($task->id, $items[0]->task);

        midcom::get()->auth->drop_sudo();
    }

    public function testGenerate_invoice_number()
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

        $calculator = new org_openpsa_sales_calculator_default();

        $exp = $previous + 1;
        $stat = $calculator->generate_invoice_number();
        $this->assertEquals($exp, $stat);
    }
}
