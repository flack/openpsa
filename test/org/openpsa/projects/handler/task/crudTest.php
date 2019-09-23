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
class org_openpsa_projects_handler_task_crudTest extends openpsa_testcase
{
    protected static $_project;
    protected static $_task;

    public static function setupBeforeClass()
    {
        self::create_user(true);

        self::$_project = self::create_class_object(org_openpsa_projects_project::class);
        self::$_task = self::create_class_object(org_openpsa_projects_task_dba::class, ['project' => self::$_project->id]);
    }

    public function testHandler_create()
    {
        midcom::get()->auth->request_sudo('org.openpsa.projects');

        $data = $this->run_handler('org.openpsa.projects', ['task', 'new']);
        $this->assertEquals('task-new', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_create2()
    {
        midcom::get()->auth->request_sudo('org.openpsa.projects');

        $data = $this->run_handler('org.openpsa.projects', ['task', 'new', 'project', self::$_project->guid]);
        $this->assertEquals('task-new-2', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_read()
    {
        midcom::get()->auth->request_sudo('org.openpsa.projects');

        $data = $this->run_handler('org.openpsa.projects', ['task', self::$_task->guid]);
        $this->assertEquals('task_view',  $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_update()
    {
        $person = self::create_user(true);
        midcom::get()->auth->request_sudo('org.openpsa.projects');

        $data = $this->run_handler('org.openpsa.projects', ['task', 'edit', self::$_task->guid]);
        $this->assertEquals('task_edit', $data['handler_id']);

        $formdata = [
            'resources' => [
                'selection' => json_encode([$person->id])
            ],
            'manager' => [
                'selection' => json_encode([$person->id])
            ],
            'project' => [
                'selection' => json_encode([self::$_project->id])
            ]
        ];

        $this->submit_dm_no_relocate_form('controller', $formdata, 'org.openpsa.projects', ['task', 'edit', self::$_task->guid]);

        self::$_task->refresh();
        $this->assertEquals(org_openpsa_projects_task_status_dba::ACCEPTED, self::$_task->status);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_delete()
    {
        midcom::get()->auth->request_sudo('org.openpsa.projects');

        $data = $this->run_handler('org.openpsa.projects', ['task', 'delete', self::$_task->guid]);
        $this->assertEquals('task_delete', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
