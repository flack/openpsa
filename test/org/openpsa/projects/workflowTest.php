<?php
class workflowTest extends PHPUnit_Framework_TestCase
{
    protected static $_user;
    protected static $_recipient;
    protected static $_project;
    protected static $_task;

    public static function setUpBeforeClass()
    {
        require_once('rootfile.php');
        self::$_user = test_helper::create_user(true);
        self::$_recipient = test_helper::create_user();

        self::$_project = new org_openpsa_projects_project();
        self::$_project->create();

        self::$_task = new org_openpsa_projects_task_dba();
        self::$_task->project = $project->id;
        self::$_task->create();
    }

    public function testProposeToOther()
    {
        $stat = org_openpsa_projects_workflow::propose(self::$_task, self::$_recipient->id, 'test comment');
        $this->assertTrue($stat);
        self::$_task = new org_openpsa_projects_task_dba(self::$_task->id);
        $this->assertEquals(ORG_OPENPSA_TASKSTATUS_PROPOSED, self::$_task->status);
        $this->assertEquals('not_started', self::$_task->status_type);
        $this->assertEquals('test comment', self::$_task->status_comment);

        $qb = org_openpsa_projects_task_status_dba::new_query_builder();
        $qb->add_constraint('task', '=', self::$_task->id);
        $result = $qb->execute();
        $this->assertEquals(sizeof($result), 1);
        $status = $result[0];
        $this->assertEquals($status->targetPerson, self::$_recipient->id);
    }

    public function testProposeToSelf()
    {
        $stat = org_openpsa_projects_workflow::propose(self::$_task, self::$_user->id, 'test comment');
        $this->assertTrue($stat);
        self::$_task = new org_openpsa_projects_task_dba(self::$_task->id);
        $this->assertEquals(ORG_OPENPSA_TASKSTATUS_ACCEPTED, self::$_task->status);
        $this->assertEquals('not_started', self::$_task->status_type);
        $this->assertEquals('test comment', self::$_task->status_comment);

        $qb = org_openpsa_projects_task_status_dba::new_query_builder();
        $qb->add_constraint('task', '=', self::$_task->id);
        $result = $qb->execute();
        $this->assertEquals(sizeof($result), 2);
        $this->assertEquals($result[0]->targetPerson, self::$_user->id);
        $this->assertEquals($result[1]->targetPerson, 0);
    }

    protected function tearDown()
    {
        $_MIDCOM->auth->request_sudo('org.openpsa.projects');
        $qb = org_openpsa_projects_task_status_dba::new_query_builder();
        $qb->add_constraint('task', '=', self::$_task->id);
        $results = $qb->execute();
        foreach ($results as $result)
        {
            $result->delete();
        };

        self::$_task->status = 0;
        self::$_task->update();
        $_MIDCOM->auth->drop_sudo();
    }

    public static function TearDownAfterClass()
    {
        $_MIDCOM->auth->request_sudo('org.openpsa.projects');
        $qb = org_openpsa_projects_task_status_dba::new_query_builder();
        $qb->add_constraint('task', '=', self::$_task->id);
        $results = $qb->execute();
        foreach ($results as $result)
        {
            $result->delete();
        }
        self::$_task->delete();
        self::$_project->delete();
        self::$_recipient->delete();
        $_MIDCOM->auth->drop_sudo();

        $_MIDCOM->auth->logout();
        self::$_user->delete();
    }
}
?>