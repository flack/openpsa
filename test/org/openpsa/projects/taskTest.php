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
    public function testCRUD()
    {
        midcom::get()->auth->request_sudo('org.openpsa.projects');

        $task = new org_openpsa_projects_task_dba();
        $task->_use_activitystream = false;
        $task->_use_rcs = false;

        $stat = $task->create();
        $this->assertFalse($stat);

        $project = $this->create_object('org_openpsa_projects_project');
        $task->project = $project->id;

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
        $project = $this->create_object('org_openpsa_projects_project');
        $task = $this->create_object('org_openpsa_projects_task_dba', ['project' => $project->id]);

        midcom::get()->auth->request_sudo('org.openpsa.projects');
        $parent = $task->get_parent();
        midcom::get()->auth->drop_sudo();

        $this->assertEquals($parent->guid, $project->guid);
    }
}
