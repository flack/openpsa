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
class org_openpsa_projects_handler_csvTest extends openpsa_testcase
{
    public function testHandler_csv()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('org.openpsa.projects');
        $project = $this->create_object('org_openpsa_projects_project');
        $task = $this->create_object('org_openpsa_projects_task_dba', array('project' => $project->id));

        $_POST['guids'] = array($task->guid);

        $data = $this->run_handler('org.openpsa.projects', array('csv', 'task'));
        $this->assertEquals('csv', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
?>