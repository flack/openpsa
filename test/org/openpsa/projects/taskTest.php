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
class org_openpsa_projects_taskTest extends openpsa_testcase
{
    private static $project;

    public static function setUpBeforeClass()
    {
        self::$project = self::create_class_object(org_openpsa_projects_project::class);
    }

    public function testCRUD()
    {
        midcom::get()->auth->request_sudo('org.openpsa.projects');

        $task = new org_openpsa_projects_task_dba();
        $task->_use_rcs = false;

        $stat = $task->create();
        $this->assertFalse($stat);

        $task->project = self::$project->id;

        $stat = $task->create();
        $this->assertTrue($stat, midcom_connection::get_error_string());

        $this->register_object($task);
        $this->assertEquals(org_openpsa_projects_task_dba::OBTYPE, $task->orgOpenpsaObtype);

        $task->refresh();
        $this->assertEquals('Task #' . $task->id, $task->title);
        $this->assertEquals(org_openpsa_projects_task_status_dba::PROPOSED, $task->status);
        $task->title = 'Test Task';
        $stat = $task->update();
        $this->assertTrue($stat);
        $this->assertEquals('Test Task', $task->title);

        $stat = $task->delete();
        $this->assertTrue($stat);

        midcom::get()->auth->drop_sudo();
    }

    public function testHierarchy()
    {
        $task = $this->create_object(org_openpsa_projects_task_dba::class, ['project' => self::$project->id]);

        midcom::get()->auth->request_sudo('org.openpsa.projects');
        $parent = $task->get_parent();
        midcom::get()->auth->drop_sudo();

        $this->assertEquals($parent->guid, self::$project->guid);
    }

    public function test_add_members()
    {
        $task = $this->create_object(org_openpsa_projects_task_dba::class, ['project' => self::$project->id]);

        midcom::get()->auth->request_sudo('org.openpsa.projects');
        $task->add_members('resources', [1, 2]);
        midcom::get()->auth->drop_sudo();

        $this->assertCount(2, $task->resources);

        midcom::get()->auth->request_sudo('org.openpsa.projects');
        $task->add_members('contacts', [3, 4]);
        midcom::get()->auth->drop_sudo();

        $this->assertCount(2, $task->contacts);
    }

    public function test_update_cache()
    {
        $invoice = $this->create_object(org_openpsa_invoices_invoice_dba::class);
        $task = $this->create_object(org_openpsa_projects_task_dba::class, ['project' => self::$project->id]);
        $data = [
            'task' => $task->id,
            'hours' => 4,
            'invoiceable' => true,
            'invoice' => $invoice->id
        ];
        $this->create_object(org_openpsa_expenses_hour_report_dba::class, $data);
        $task->update_cache();
        $this->assertEquals(4, $task->invoicedHours);

        $data['invoiceable'] = false;
        $this->create_object(org_openpsa_expenses_hour_report_dba::class, $data);
        $task->update_cache();
        $this->assertEquals(4, $task->invoicedHours);
        $this->assertEquals(8, $task->reportedHours);

        $data['invoiceable'] = true;
        unset($data['invoice']);
        $this->create_object(org_openpsa_expenses_hour_report_dba::class, $data);
        $task->update_cache();
        $this->assertEquals(4, $task->invoiceableHours);
        $this->assertEquals(12, $task->reportedHours);
    }
}
