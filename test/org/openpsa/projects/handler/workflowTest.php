<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\projects\handler;

use openpsa_testcase;
use midcom;
use org_openpsa_projects_project;
use org_openpsa_projects_task_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class workflowTest extends openpsa_testcase
{
    public function testHandler_post()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('org.openpsa.projects');

        $project = $this->create_object(org_openpsa_projects_project::class);
        $task = $this->create_object(org_openpsa_projects_task_dba::class, ['project' => $project->id]);

        $_POST = [
            'org_openpsa_projects_workflow_action' => ['accept' => true],
            'org_openpsa_projects_workflow_action_redirect' => 'test'
        ];

        $url = $this->run_relocate_handler('org.openpsa.projects', ['workflow', $task->guid]);
        $this->assertEquals('test', $url);

        midcom::get()->auth->drop_sudo();
    }
}
