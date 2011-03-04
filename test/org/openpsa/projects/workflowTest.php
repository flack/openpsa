<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR);
    require_once(OPENPSA_TEST_ROOT . 'rootfile.php');
}

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

        self::$_project = self::create_class_object('org_openpsa_projects_project');
        self::$_task = self::create_class_object('org_openpsa_projects_task_dba', array('up' => self::$_project->id));
    }

    public function testProposeToOther()
    {
        $stat = org_openpsa_projects_workflow::propose(self::$_task, self::$_other_user->id, 'test comment');
        $this->assertTrue($stat);
        self::$_task->refresh();
        $this->assertEquals(ORG_OPENPSA_TASKSTATUS_PROPOSED, self::$_task->status);
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
        $this->assertEquals(ORG_OPENPSA_TASKSTATUS_ACCEPTED, self::$_task->status);
        $this->assertEquals('not_started', self::$_task->status_type);
        $this->assertEquals('test comment', self::$_task->status_comment);

        $qb = org_openpsa_projects_task_status_dba::new_query_builder();
        $qb->add_constraint('task', '=', self::$_task->id);
        $result = $qb->execute();
        $this->assertEquals(sizeof($result), 2);
        $this->assertEquals($result[0]->targetPerson, self::$_user->id);
        $this->assertEquals($result[1]->targetPerson, 0);

        $qb = org_openpsa_projects_task_status_dba::new_query_builder();
        $qb->add_constraint('task', '=', self::$_project->id);
        $result = $qb->execute();
        $this->assertEquals(sizeof($result), 1);
        $this->assertEquals(ORG_OPENPSA_TASKSTATUS_ACCEPTED, $result[0]->type);
        self::$_project->refresh();
        $this->assertEquals(ORG_OPENPSA_TASKSTATUS_ACCEPTED, self::$_project->status);
    }

    public function testCompleteOwnTask()
    {
        $stat = org_openpsa_projects_workflow::complete(self::$_task, 'test comment');
        $this->assertTrue($stat);
        self::$_task->refresh();
        $this->assertEquals(ORG_OPENPSA_TASKSTATUS_CLOSED, self::$_task->status);
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
        $this->assertEquals(ORG_OPENPSA_TASKSTATUS_COMPLETED, self::$_task->status);
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
        $this->assertEquals(ORG_OPENPSA_TASKSTATUS_CLOSED, self::$_task->status);
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
        $this->assertEquals(ORG_OPENPSA_TASKSTATUS_CLOSED, self::$_task->status);
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

    public function tearDown()
    {
        self::delete_linked_objects('org_openpsa_projects_task_status_dba', 'task', self::$_task->id);
        self::delete_linked_objects('org_openpsa_projects_task_status_dba', 'task', self::$_project->id);

        self::$_task->status = 0;
        self::$_task->manager = self::$_user->id;
        self::$_task->update();

        self::$_project->status = 0;
        self::$_project->update();
        $_MIDCOM->auth->drop_sudo();
    }

    public static function TearDownAfterClass()
    {
        parent::TearDownAfterClass();

        $_MIDCOM->auth->request_sudo('org.openpsa.projects');
        self::$_other_user->delete();
        $_MIDCOM->auth->drop_sudo();

        $_MIDCOM->auth->logout();
        self::$_user->delete();
    }
}
?>