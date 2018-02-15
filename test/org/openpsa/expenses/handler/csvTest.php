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
class org_openpsa_expenses_handler_csvTest extends openpsa_testcase
{
    public function testHandler_csv()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('org.openpsa.expenses');
        $project = $this->create_object('org_openpsa_projects_project');
        $task = $this->create_object('org_openpsa_projects_task_dba', ['project' => $project->id]);
        $report = $this->create_object('org_openpsa_expenses_hour_report_dba', ['task' => $task->id]);

        $_POST['ids'] = [$report->id];

        $data = $this->run_handler('org.openpsa.expenses', ['csv', 'hours']);
        $this->assertEquals('csv', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
