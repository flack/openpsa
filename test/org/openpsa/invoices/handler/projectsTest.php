<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\invoices\handler;

use midcom_db_person;
use openpsa_testcase;
use midcom;
use org_openpsa_contacts_group_dba;
use org_openpsa_sales_salesproject_dba;
use org_openpsa_sales_salesproject_deliverable_dba;
use org_openpsa_projects_task_status_dba;
use org_openpsa_projects_task_dba;
use org_openpsa_invoices_invoice_item_dba;
use org_openpsa_invoices_invoice_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class projectsTest extends openpsa_testcase
{
    protected static midcom_db_person $_person;

    public static function setUpBeforeClass() : void
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_uninvoiced()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');

        $data = $this->run_handler('org.openpsa.invoices', ['projects']);
        $this->assertEquals('list_projects_uninvoiced', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function test_generate_invoice()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');

        $customer = $this->create_object(org_openpsa_contacts_group_dba::class);
        $salesproject = $this->create_object(org_openpsa_sales_salesproject_dba::class);
        $deliverable_attributes = [
            'salesproject' => $salesproject->id,
            'price' => 100,
            'state' => org_openpsa_sales_salesproject_deliverable_dba::STATE_DELIVERED,
            'invoiceByActualUnits' => false
        ];
        $deliverable = $this->create_object(org_openpsa_sales_salesproject_deliverable_dba::class, $deliverable_attributes);
        $task_attributes = [
            'project' => $salesproject->id,
            'agreement' => $deliverable->id,
            'status' => org_openpsa_projects_task_status_dba::COMPLETED,
            'invoiceableHours' => true
        ];
        $task = $this->create_object(org_openpsa_projects_task_dba::class, $task_attributes);

        $_POST = [
            'org_openpsa_invoices_invoice' => true,
            'org_openpsa_invoices_invoice_tasks' => [$task->id => true],
            'org_openpsa_invoices_invoice_tasks_price' => [$task->id => 10],
            'org_openpsa_invoices_invoice_tasks_units' => [$task->id => 10],
            'org_openpsa_invoices_invoice_customer' => $customer->id
        ];

        $url = $this->run_relocate_handler('org.openpsa.invoices', ['projects']);

        $qb = org_openpsa_invoices_invoice_item_dba::new_query_builder();
        $qb->add_constraint('task', '=', $task->id);
        $results = $qb->execute();
        $this->register_objects($results);
        $this->assertCount(1, $results);
        $item = $results[0];
        $invoice = new org_openpsa_invoices_invoice_dba($item->invoice);
        $this->register_object($invoice);
        $task->refresh();

        $this->assertEquals('invoice/' . $invoice->guid . '/', $url);
        $this->assertEquals(100, $invoice->sum);
        $this->assertEquals($deliverable->id, $item->deliverable);

        $deliverable->refresh();
        $this->assertEquals(org_openpsa_sales_salesproject_deliverable_dba::STATE_INVOICED, $deliverable->state);

        midcom::get()->auth->drop_sudo();
    }
}
