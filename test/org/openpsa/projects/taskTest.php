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
class org_openpsa_projects_taskTest extends openpsa_testcase
{
    public function testCRUD()
    {
        $_MIDCOM->auth->request_sudo('org.openpsa.projects');

        $task = new org_openpsa_projects_task_dba();
        $stat = $task->create();
        $this->assertFalse($stat);

        $project = $this->create_object('org_openpsa_projects_project');
        $task->project = $project->id;
        $stat = $task->create();
        $this->assertTrue($stat);
        $this->register_object($task);
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
        $project = $this->create_object('org_openpsa_projects_project');
        $task = $this->create_object('org_openpsa_projects_task_dba', array('project' => $project->id));

        $_MIDCOM->auth->request_sudo('org.openpsa.projects');
        $parent = $task->get_parent();
        $_MIDCOM->auth->drop_sudo();

        $this->assertEquals($parent->guid, $project->guid);
    }
}
?>