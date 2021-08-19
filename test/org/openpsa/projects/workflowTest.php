<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\projects;

use openpsa_testcase;
use org_openpsa_projects_project;
use org_openpsa_projects_task_dba;
use org_openpsa_projects_task_status_dba;
use org_openpsa_projects_workflow;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class workflowTest extends openpsa_testcase
{
    protected static $_user;
    protected static $_other_user;
    protected static $_project;
    protected static $_task;

    public static function setUpBeforeClass() : void
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
        $this->assertCount(1, $result);
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
        $this->assertCount(2, $result);
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
        $this->assertCount(3, $result);
        $status = $result[0];
        $this->assertEquals(0, $status->targetPerson);
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
        $this->assertCount(1, $result);
        $status = $result[0];
        $this->assertEquals(0, $status->targetPerson);
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
        $this->assertCount(3, $result);
        $status = $result[0];
        $this->assertEquals(0, $status->targetPerson);
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
        $this->assertCount(2, $result);
        $status = $result[0];
        $this->assertEquals(0, $status->targetPerson);
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

    public function tearDown() : void
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
