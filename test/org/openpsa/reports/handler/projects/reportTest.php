<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\reports\handler\projects;

use openpsa_testcase;
use midcom;
use org_openpsa_reports_query_dba;
use org_openpsa_projects_project;
use org_openpsa_projects_task_dba;
use org_openpsa_expenses_hour_report_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class reportTest extends openpsa_testcase
{
    private static org_openpsa_projects_project $project;

    public static function setUpBeforeClass() : void
    {
        self::create_user(true);
        self::$project = self::create_class_object(org_openpsa_projects_project::class);
        $task = self::create_class_object(org_openpsa_projects_task_dba::class, ['project' => self::$project->id]);
        self::create_class_object(org_openpsa_expenses_hour_report_dba::class, ['task' => $task->id]);
    }

    public function test_handler_generator_get()
    {
        midcom::get()->auth->request_sudo('org.openpsa.reports');

        $_GET = ['org_openpsa_reports_query_data' => [
            'mimetype' => 'text/html',
            'end' => time() + 10000,
            'start' => time() - 10000,
            'resource' => 'all',
            'task' => self::$project->guid
        ]];

        $data = $this->run_handler('org.openpsa.reports', ['projects', 'get']);
        $this->assertEquals('projects_report_get', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function test_handler_edit_report_guid()
    {
        midcom::get()->auth->request_sudo('org.openpsa.reports');

        $query = $this->create_object(org_openpsa_reports_query_dba::class);

        $data = $this->run_handler('org.openpsa.reports', ['projects', 'edit', $query->guid]);
        $this->assertEquals('projects_edit_report_guid', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function test_handler_report_guid_file()
    {
        midcom::get()->auth->request_sudo('org.openpsa.reports');

        $query = $this->create_object(org_openpsa_reports_query_dba::class);
        $query->set_parameter('midcom.helper.datamanager2', 'task', self::$project->guid);
        $query->set_parameter('midcom.helper.datamanager2', 'resource', 'all');

        $data = $this->run_handler('org.openpsa.reports', 'projects/' . $query->guid . '/test.csv');
        $this->assertEquals('projects_report_guid_file', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function test_handler_report_guid()
    {
        midcom::get()->auth->request_sudo('org.openpsa.reports');

        $query = $this->create_object(org_openpsa_reports_query_dba::class);
        $timestamp = date('Y_m_d', $query->metadata->created);

        $url = $this->run_relocate_handler('org.openpsa.reports', ['projects', $query->guid]);

        $this->assertEquals('projects/' . $query->guid . '/' . $timestamp . '_projects.html', $url);

        midcom::get()->auth->drop_sudo();
    }

    public function test_handler_report()
    {
        midcom::get()->auth->request_sudo('org.openpsa.reports');

        $this->create_object(org_openpsa_reports_query_dba::class);

        $data = $this->run_handler('org.openpsa.reports', ['projects']);
        $this->assertEquals('projects_report', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
