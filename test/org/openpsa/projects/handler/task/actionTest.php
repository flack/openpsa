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
class org_openpsa_projects_handler_task_actionTest extends openpsa_testcase
{
    public function testHandler_action()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('org.openpsa.projects');

        $project = $this->create_object('org_openpsa_projects_project');
        $task = $this->create_object('org_openpsa_projects_task_dba', array('project' => $project->id));

        $url = $this->run_relocate_handler('org.openpsa.projects', array('task', $task->guid, 'complete'));
        $this->assertEquals('task/' . $task->guid . '/', $url);

        midcom::get()->auth->drop_sudo();
    }
}
?>