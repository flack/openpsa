<?php
require_once('rootfile.php');

class reportTest extends openpsa_testcase
{
    protected static $_task;
    protected static $_project;

    public static function setUpBeforeClass()
    {
        $_MIDCOM->auth->request_sudo('org.openpsa.projects');
        self::$_project = new org_openpsa_projects_project();
        self::$_project->create();

        self::$_task = new org_openpsa_projects_task_dba();
        self::$_task->up = self::$_project->id;
        self::$_task->create();

        $_MIDCOM->auth->drop_sudo();
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

        self::$_task = new org_openpsa_projects_task_dba(self::$_task->guid);
        $this->assertEquals(self::$_task->reportedHours, 2.5);
        $task_hours = self::$_project->get_task_hours();
        $this->assertEquals($task_hours['reportedHours'], 2.5);


        $stat = $report->delete();
        $this->assertTrue($stat);

        self::$_task = new org_openpsa_projects_task_dba(self::$_task->guid);
        $this->assertEquals(self::$_task->reportedHours, 0);
        $task_hours = self::$_project->get_task_hours();
        $this->assertEquals($task_hours['reportedHours'], 0);

        $_MIDCOM->auth->drop_sudo();
    }

    public function tearDown()
    {
        $_MIDCOM->auth->request_sudo('org.openpsa.projects');
        $qb = org_openpsa_projects_hour_report_dba::new_query_builder();
        $qb->add_constraint('task', '=', self::$_task->id);
        $results = $qb->execute();
        foreach ($results as $result)
        {
            $result->delete();
        }
        $_MIDCOM->auth->drop_sudo();
    }

    public static function TearDownAfterClass()
    {
        $_MIDCOM->auth->request_sudo('org.openpsa.projects');
        self::$_task->delete();
        self::$_project->delete();
        $_MIDCOM->auth->drop_sudo();
    }
}
?>