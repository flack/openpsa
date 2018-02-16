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
class org_openpsa_projects_handler_task_listTest extends openpsa_testcase
{
    protected static $_project;

    public static function setupBeforeClass()
    {
        $user = self::create_user(true);

        self::$_project = self::create_class_object(org_openpsa_projects_project::class);
        $attributes = [
            'manager' => $user->id,
            'project' => self::$_project->id
        ];
        self::create_class_object(org_openpsa_projects_task_dba::class, $attributes);
    }

    public function testHandler_list_user()
    {
        midcom::get()->auth->request_sudo('org.openpsa.projects');

        $data = $this->run_handler('org.openpsa.projects', ['task', 'list']);
        $this->assertEquals('task-list-user', $data['handler_id']);

        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_list_project()
    {
        midcom::get()->auth->request_sudo('org.openpsa.projects');

        $data = $this->run_handler('org.openpsa.projects', ['task', 'list', 'project', self::$_project->guid]);
        $this->assertEquals('task-list-project', $data['handler_id']);

        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_list_project_json()
    {
        midcom::get()->auth->request_sudo('org.openpsa.projects');

        $data = $this->run_handler('org.openpsa.projects', ['task', 'list', 'json', self::$_project->guid]);
        $this->assertEquals('task-list-json', $data['handler_id']);

        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_list()
    {
        midcom::get()->auth->request_sudo('org.openpsa.projects');

        $data = $this->run_handler('org.openpsa.projects', ['task', 'list', 'open']);
        $this->assertEquals('task-list', $data['handler_id']);

        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_list_all_deliverable()
    {
        midcom::get()->auth->request_sudo('org.openpsa.projects');

        $salesproject = $this->create_object(org_openpsa_sales_salesproject_dba::class);
        $deliverable = $this->create_object(org_openpsa_sales_salesproject_deliverable_dba::class, ['salesproject' => $salesproject->id]);

        $data = $this->run_handler('org.openpsa.projects', ['task', 'list', 'agreement', $deliverable->id]);
        $this->assertEquals('task-list-agreement', $data['handler_id']);
        $this->show_handler($data);

        midcom::get()->auth->drop_sudo();
    }
}
