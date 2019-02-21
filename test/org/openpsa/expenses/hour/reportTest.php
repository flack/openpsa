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

    public static function setUpBeforeClass()
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
        $stat = $report->create();
        $this->assertTrue($stat);
        $this->register_object($report);

        $parent = $report->get_parent();
        $this->assertEquals($parent->guid, self::$_task->guid);

        self::$_task->refresh();
        $this->assertEquals(2.5, self::$_task->reportedHours);
        $task_hours = self::$_project->get_task_hours();
        $this->assertEquals(2.5, $task_hours['reportedHours']);

        $report->invoiceable = true;
        $report->hours = 3.5;
        $stat = $report->update();

        $this->assertTrue($stat);
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

        midcom::get()->auth->request_sudo('org.openpsa.projects');

        self::$_task->refresh();
        $this->assertEquals(2.5, self::$_task->invoiceableHours);

        $report->task = $task2->id;
        $report->update();
        self::$_task->refresh();
        $this->assertEquals(0, self::$_task->invoiceableHours);

        midcom::get()->auth->drop_sudo();
    }
}
