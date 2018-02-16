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
class org_openpsa_projects_workflowTest extends openpsa_testcase
{
    protected static $_user;
    protected static $_other_user;
    protected static $_project;
    protected static $_task;

    public static function setUpBeforeClass()
    {
        self::$_user = self::create_user(true);
        self::$_other_user = self::create_user();

        self::$_project = self::create_class_object(org_openpsa_projects_project::class);
        self::$_task = self::create_class_object(org_openpsa_projects_task_dba::class, ['project' => self::$_project->id]);
    }

    public function testProposeToOther()
    {
        $stat = org_openpsa_projects_workflow::propose(self::$_task, self::$_other_user->id, 'test comment');
        $this->assertTrue($stat);
        self::$_task->refresh();
        $this->assertEquals(org_openpsa_projects_task_status_dba::PROPOSED, self::$_task->status);
        $this->assertEquals('not_started', self::$_task->status_type);
        $this->assertEquals('test comment', self::$_task->status_comment);

        $qb = org_openpsa_projects_task_status_dba::new_query_builder();
        $qb->add_constraint('task', '=', self::$_task->id);
        $result = $qb->execute();
        $this->assertEquals(sizeof($result), 1);
        $status = $result[0];
        $this->assertEquals($status->targetPerson, self::$_other_user->id);
    }

    public function testProposeToSelf()
    {
        $stat = org_openpsa_projects_workflow::propose(self::$_task, self::$_user->id, 'test comment');
        $this->assertTrue($stat);

        self::$_task->refresh();
        $this->assertEquals(org_openpsa_projects_task_status_dba::ACCEPTED, self::$_task->status);
        $this->assertEquals('not_started', self::$_task->status_type);
        $this->assertEquals('test comment', self::$_task->status_comment);

        $qb = org_openpsa_projects_task_status_dba::new_query_builder();
        $qb->add_constraint('task', '=', self::$_task->id);
        $qb->add_order('type');
        $result = $qb->execute();
        $this->assertEquals(sizeof($result), 2);
        $this->assertEquals(self::$_user->id, $result[0]->targetPerson);
        $this->assertEquals(0, $result[1]->targetPerson);

        self::$_project->refresh();
        $this->assertEquals(org_openpsa_projects_task_status_dba::ACCEPTED, self::$_project->status);
    }

    public function testCompleteOwnTask()
    {
        $stat = org_openpsa_projects_workflow::complete(self::$_task, 'test comment');
        $this->assertTrue($stat);
        self::$_task->refresh();
        $this->assertEquals(org_openpsa_projects_task_status_dba::CLOSED, self::$_task->status);
        $this->assertEquals('closed', self::$_task->status_type);
        $this->assertEquals('test comment', self::$_task->status_comment);

        $qb = org_openpsa_projects_task_status_dba::new_query_builder();
        $qb->add_constraint('task', '=', self::$_task->id);
        $result = $qb->execute();
        $this->assertEquals(sizeof($result), 3);
        $status = $result[0];
        $this->assertEquals($status->targetPerson, 0);
    }

    public function testCompleteOthersTask()
    {
        self::$_task->manager = self::$_other_user->id;
        self::$_task->update();
        self::$_task->refresh();

        $stat = org_openpsa_projects_workflow::complete(self::$_task, 'test comment');
        $this->assertTrue($stat);

        self::$_task->refresh();
        $this->assertEquals(org_openpsa_projects_task_status_dba::COMPLETED, self::$_task->status);
        $this->assertEquals('closed', self::$_task->status_type);
        $this->assertEquals('test comment', self::$_task->status_comment);

        $qb = org_openpsa_projects_task_status_dba::new_query_builder();
        $qb->add_constraint('task', '=', self::$_task->id);
        $result = $qb->execute();
        $this->assertEquals(sizeof($result), 1);
        $status = $result[0];
        $this->assertEquals($status->targetPerson, 0);
    }

    public function testCompleteUnmanagedTask()
    {
        self::$_task->manager = 0;
        self::$_task->update();
        self::$_task->refresh();

        $stat = org_openpsa_projects_workflow::complete(self::$_task, 'test comment');

        $this->assertTrue($stat);
        self::$_task->refresh();
        $this->assertEquals(org_openpsa_projects_task_status_dba::CLOSED, self::$_task->status);
        $this->assertEquals('closed', self::$_task->status_type);
        $this->assertEquals('test comment', self::$_task->status_comment);

        $qb = org_openpsa_projects_task_status_dba::new_query_builder();
        $qb->add_constraint('task', '=', self::$_task->id);
        $result = $qb->execute();
        $this->assertEquals(sizeof($result), 3);
        $status = $result[0];
        $this->assertEquals($status->targetPerson, 0);
    }

    public function testApproveOwnTask()
    {
        $stat = org_openpsa_projects_workflow::approve(self::$_task, 'test comment');
        $this->assertTrue($stat);
        self::$_task->refresh();
        $this->assertEquals(org_openpsa_projects_task_status_dba::CLOSED, self::$_task->status);
        $this->assertEquals('closed', self::$_task->status_type);
        $this->assertEquals('test comment', self::$_task->status_comment);

        $qb = org_openpsa_projects_task_status_dba::new_query_builder();
        $qb->add_constraint('task', '=', self::$_task->id);
        $result = $qb->execute();
        $this->assertEquals(sizeof($result), 2);
        $status = $result[0];
        $this->assertEquals($status->targetPerson, 0);
    }

    public function testApproveOthersTask()
    {
        self::$_task->manager = self::$_other_user->id;
        self::$_task->update();
        self::$_task->refresh();

        $stat = org_openpsa_projects_workflow::approve(self::$_task, 'test comment');
        $this->assertFalse($stat);
    }


    public function testDeclineTask()
    {
        self::$_task->manager = self::$_other_user->id;
        self::$_task->update();
        self::$_task->refresh();

        $stat = org_openpsa_projects_workflow::decline(self::$_task, 'test comment');
        $this->assertTrue($stat);
    }

    public function test_mark_invoiced()
    {
        $group = $this->create_object(org_openpsa_products_product_group_dba::class);

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
        self::$_task->agreement = $deliverable->id;
        self::$_task->update();

        $report_attributes = [
            'task' => self::$_task->id,
            'invoiceable' => true,
            'hours' => 15
        ];
        $report = $this->create_object(org_openpsa_expenses_hour_report_dba::class, $report_attributes);

        $invoice = $this->create_object(org_openpsa_invoices_invoice_dba::class);
        $result = org_openpsa_projects_workflow::mark_invoiced(self::$_task, $invoice);

        $this->assertEquals(15, $result);
        $report->refresh();
        $this->assertEquals($invoice->id, $report->invoice);
    }

    public function tearDown()
    {
        self::delete_linked_objects('org_openpsa_projects_task_status_dba', 'task', self::$_task->id);
        self::delete_linked_objects('org_openpsa_projects_task_status_dba', 'task', self::$_project->id);

        self::$_task->status = 0;
        self::$_task->manager = self::$_user->id;
        self::$_task->update();

        self::$_project->status = 0;
        self::$_project->update();
        parent::tearDown();
    }
}
