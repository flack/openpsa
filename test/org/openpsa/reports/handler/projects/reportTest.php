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
class org_openpsa_reports_handler_projects_reportTest extends openpsa_testcase
{
    private static $project;

    public static function setUpBeforeClass()
    {
        self::create_user(true);
        self::$project = self::create_class_object(org_openpsa_projects_project::class);
        $task = self::create_class_object(org_openpsa_projects_task_dba::class, ['project' => self::$project->id]);
        self::create_class_object(org_openpsa_expenses_hour_report_dba::class, ['task' => $task->id]);
    }

    public function test_handler_generator_get()
    {
        midcom::get()->auth->request_sudo('org.openpsa.reports');

        $_REQUEST = ['org_openpsa_reports_query_data' => [
            'mimetype' => 'text/html',
            'end' => time() + 10000,
            'start' => time() - 10000,
            'resource' => 'all',
            'task' => self::$project->guid
        ]];

        $data = $this->run_handler('org.openpsa.reports', ['projects', 'get']);
        $this->assertEquals('projects_report_get', $data['handler_id']);

        $this->show_handler($data);
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

        $data = $this->run_handler('org.openpsa.reports', ['projects', $query->guid, 'test.csv']);
        $this->assertEquals('projects_report_guid_file', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function test_handler_report_guid()
    {
        midcom::get()->auth->request_sudo('org.openpsa.reports');

        $query = $this->create_object(org_openpsa_reports_query_dba::class);
        $timestamp = strftime('%Y_%m_%d', $query->metadata->created);

        $url = $this->run_relocate_handler('org.openpsa.reports', ['projects', $query->guid]);

        $this->assertEquals('projects/' . $query->guid . '/' . $timestamp . '_projects.html', $url);

        midcom::get()->auth->drop_sudo();
    }

    public function test_handler_report()
    {
        midcom::get()->auth->request_sudo('org.openpsa.reports');

        $query = $this->create_object(org_openpsa_reports_query_dba::class);

        $data = $this->run_handler('org.openpsa.reports', ['projects']);
        $this->assertEquals('projects_report', $data['handler_id']);

        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }
}
