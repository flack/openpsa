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
class org_openpsa_projects_handler_workflowTest extends openpsa_testcase
{
    public function testHandler_post()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('org.openpsa.projects');

        $project = $this->create_object('org_openpsa_projects_project');
        $task = $this->create_object('org_openpsa_projects_task_dba', ['project' => $project->id]);

        $_POST = [
            'org_openpsa_projects_workflow_action' => ['accept' => true],
            'org_openpsa_projects_workflow_action_redirect' => 'test'
        ];

        $url = $this->run_relocate_handler('org.openpsa.projects', ['workflow', $task->guid]);
        $this->assertEquals('test', $url);

        midcom::get()->auth->drop_sudo();
    }
}
