<?php
require_once('rootfile.php');

class reportTest extends openpsa_testcase
{
    protected static $_task;
    protected static $_project;

    public static function setUpBeforeClass()
    {
        self::$_project = self::create_class_object('org_openpsa_projects_project');
        self::$_task = self::create_class_object('org_openpsa_projects_task_dba', array('up' => self::$_project->id));
    }

    public function testCRUD()
    {
        $_MIDCOM->auth->request_sudo('org.openpsa.projects');
        $report = new org_openpsa_projects_hour_report_dba();
        $report->task = self::$_task->id;
        $report->hours = 2.5;
        $stat = $report->create();
        $this->assertTrue($stat);

        $parent = $report->get_parent();
        $this->assertEquals($parent->guid, self::$_task->guid);

        self::$_task->refresh();
        $this->assertEquals(self::$_task->reportedHours, 2.5);
        $task_hours = self::$_project->get_task_hours();
        $this->assertEquals($task_hours['reportedHours'], 2.5);


        $stat = $report->delete();
        $this->assertTrue($stat);

        self::$_task->refresh();
        $this->assertEquals(self::$_task->reportedHours, 0);
        $task_hours = self::$_project->get_task_hours();
        $this->assertEquals($task_hours['reportedHours'], 0);

        $_MIDCOM->auth->drop_sudo();
    }

    public function tearDown()
    {
        self::delete_linked_objects('org_openpsa_projects_hour_report_dba', 'task', self::$_task->id);
    }
}
?>