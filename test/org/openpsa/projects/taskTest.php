<?php
require_once('rootfile.php');

class taskTest extends openpsa_testcase
{
    public function testCRUD()
    {
        $_MIDCOM->auth->request_sudo('org.openpsa.projects');

        $task = new org_openpsa_projects_task_dba();
        $stat = $task->create();
        $this->assertTrue($stat);
        $this->assertEquals(ORG_OPENPSA_OBTYPE_TASK, $task->orgOpenpsaObtype);

        $task->refresh();
        $this->assertEquals('Task #' . $task->id, $task->title);
        $this->assertEquals(ORG_OPENPSA_TASKSTATUS_PROPOSED, $task->status);
        $task->title = 'Test Task';
        $stat = $task->update();
        $this->assertTrue($stat);
        $this->assertEquals('Test Task', $task->title);

        $stat = $task->delete();
        $this->assertTrue($stat);

        $_MIDCOM->auth->drop_sudo();
    }

    public function testHierarchy()
    {
        $_MIDCOM->auth->request_sudo('org.openpsa.projects');

        $project = new org_openpsa_projects_project();
        $project->create();

        $task = new org_openpsa_projects_task_dba();

        $task->up = $project->id;
        $task->create();

        $parent = $task->get_parent();

        $this->assertEquals($parent->guid, $project->guid);

        $task->delete();
        $project->delete();

        $_MIDCOM->auth->drop_sudo();
    }

}
?>