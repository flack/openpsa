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
class org_openpsa_expenses_hour_reportTest extends openpsa_testcase
{
    /**
     * @var org_openpsa_projects_task_dba
     */
    protected static $_task;
    protected static $_project;

    public static function setUpBeforeClass() : void
    {
        self::$_project = self::create_class_object(org_openpsa_projects_project::class);
        self::$_task = self::create_class_object(org_openpsa_projects_task_dba::class, ['project' => self::$_project->id]);
    }

    public function testCRUD()
    {
        midcom::get()->auth->request_sudo('org.openpsa.projects');
        $report = new org_openpsa_expenses_hour_report_dba();
        $report->_use_rcs = false;

        $report->task = self::$_task->id;
        $report->hours = 2.5;
        $this->assertTrue($report->create());
        $this->register_object($report);

        $parent = $report->get_parent();
        $this->assertEquals($parent->guid, self::$_task->guid);

        self::$_task->refresh();
        $this->assertEquals(2.5, self::$_task->reportedHours);
        $task_hours = self::$_project->get_task_hours();
        $this->assertEquals(2.5, $task_hours['reportedHours']);

        $report->invoiceable = true;
        $report->hours = 3.5;
        $this->assertTrue($report->update());

        self::$_task->refresh();
        $this->assertEquals(3.5, self::$_task->invoiceableHours);
        $task_hours = self::$_project->get_task_hours();
        $this->assertEquals(3.5, $task_hours['reportedHours']);

        $stat = $report->delete();
        $this->assertTrue($stat, midcom_connection::get_error_string());

        self::$_task->refresh();
        $this->assertEquals(0, self::$_task->reportedHours);
        $task_hours = self::$_project->get_task_hours();
        $this->assertEquals(0, $task_hours['reportedHours']);

        midcom::get()->auth->drop_sudo();
    }

    public function test_parent_update()
    {
        $person = $this->create_object(midcom_db_person::class);
        $task = $this->create_object(org_openpsa_projects_task_dba::class, ['project' => self::$_project->id]);

        midcom::get()->auth->request_sudo('org.openpsa.projects');
        $report = $this->create_object(org_openpsa_expenses_hour_report_dba::class, [
            'task' => $task->id,
            'hours' => 2,
            'person' => $person->id
        ]);
        $task->refresh();
        $this->assertEquals(org_openpsa_projects_task_status_dba::STARTED, $task->status);

        $task2 = $this->create_object(org_openpsa_projects_task_dba::class, ['project' => self::$_project->id]);

        $report->task = $task2->id;
        $this->assertTrue($report->update());
        $task2->refresh();
        midcom::get()->auth->drop_sudo();

        $this->assertEquals(org_openpsa_projects_task_status_dba::STARTED, $task2->status);
    }

    public function test_invoice_delete()
    {
        $person = $this->create_object(midcom_db_person::class);
        $task = $this->create_object(org_openpsa_projects_task_dba::class, ['project' => self::$_project->id]);
        $invoice = $this->create_object(org_openpsa_invoices_invoice_dba::class);

        $this->create_object(org_openpsa_expenses_hour_report_dba::class, [
            'task' => $task->id,
            'invoiceable' => true,
            'invoice' => $invoice->id,
            'hours' => 2,
            'person' => $person->id
        ]);
        $this->sudo([$task, 'refresh']);
        $this->assertEquals(2, $task->invoicedHours);

        $this->sudo([$invoice, 'delete']);
        $this->assertEquals(2, $task->invoicedHours);
    }

    public function test_get_parent()
    {
        $report = $this->create_object(org_openpsa_expenses_hour_report_dba::class, ['task' => self::$_task->id]);
        $parent = $report->get_parent();
        $this->assertEquals(self::$_task->guid, $parent->guid);
    }

    public function test_move()
    {
        $task2 = $this->create_object(org_openpsa_projects_task_dba::class, ['project' => self::$_project->id]);

        $report = $this->create_object(org_openpsa_expenses_hour_report_dba::class, [
            'task' => self::$_task->id,
            'hours' => 2.5,
            'invoiceable' => true
        ]);

        midcom::get()->auth->request_sudo('org.openpsa.expenses');

        self::$_task->refresh();
        $this->assertEquals(2.5, self::$_task->invoiceableHours);

        $report->task = $task2->id;
        $report->update();
        self::$_task->refresh();
        $this->assertEquals(0, self::$_task->invoiceableHours);

        midcom::get()->auth->drop_sudo();
    }

    public function test_update_cache()
    {
        $invoice = $this->create_object(org_openpsa_invoices_invoice_dba::class);
        $task = $this->create_object(org_openpsa_projects_task_dba::class, ['project' => self::$_project->id]);
        $data = [
            'task' => $task->id,
            'hours' => 4,
            'invoiceable' => true,
            'invoice' => $invoice->id
        ];
        $this->create_object(org_openpsa_expenses_hour_report_dba::class, $data);
        $this->sudo([$task, 'refresh']);
        $this->assertEquals(4, $task->invoicedHours);

        $data['invoiceable'] = false;
        $this->create_object(org_openpsa_expenses_hour_report_dba::class, $data);
        $this->sudo([$task, 'refresh']);
        $this->assertEquals(4, $task->invoicedHours);
        $this->assertEquals(8, $task->reportedHours);

        $data['invoiceable'] = true;
        unset($data['invoice']);
        $this->create_object(org_openpsa_expenses_hour_report_dba::class, $data);
        $this->sudo([$task, 'refresh']);
        $this->assertEquals(4, $task->invoiceableHours);
        $this->assertEquals(12, $task->reportedHours);
    }

    public function test_mark_invoiced()
    {
        $group = $this->create_object(org_openpsa_products_product_group_dba::class);
        $project = $this->create_object(org_openpsa_projects_project::class);
        $task = $this->create_object(org_openpsa_projects_task_dba::class, ['project' => $project->id]);

        $product_attributes = [
            'productGroup' => $group->id,
            'code' => 'TEST-' . __CLASS__ . time(),
            'delivery' => org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION
        ];
        $product = $this->create_object(org_openpsa_products_product_dba::class, $product_attributes);

        $salesproject = $this->create_object(org_openpsa_sales_salesproject_dba::class);

        $deliverable_attributes = [
            'salesproject' => $salesproject->id,
            'product' => $product->id,
            'description' => 'TEST DESCRIPTION',
            'plannedUnits' => 15,
        ];
        $deliverable = $this->create_object(org_openpsa_sales_salesproject_deliverable_dba::class, $deliverable_attributes);
        $task->agreement = $deliverable->id;
        $task->update();

        $report_attributes = [
            'task' => $task->id,
            'invoiceable' => true,
            'hours' => 15
        ];
        $report = $this->create_object(org_openpsa_expenses_hour_report_dba::class, $report_attributes);
        unset($report_attributes['invoiceable']);
        $report2 = $this->create_object(org_openpsa_expenses_hour_report_dba::class, $report_attributes);
        $invoice = $this->create_object(org_openpsa_invoices_invoice_dba::class);

        midcom::get()->auth->request_sudo('org.openpsa.expenses');
        $result = org_openpsa_expenses_hour_report_dba::mark_invoiced($task, $invoice);
        midcom::get()->auth->drop_sudo();

        $this->assertEquals(15, $result);
        $report->refresh();
        $this->assertEquals($invoice->id, $report->invoice);
        $report2->refresh();
        $this->assertEquals($invoice->id, $report2->invoice);
    }
}
