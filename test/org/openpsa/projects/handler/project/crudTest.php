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
class org_openpsa_projects_handler_project_crudTest extends openpsa_testcase
{
    protected static $_project;

    public static function setupBeforeClass()
    {
        self::create_user(true);

        self::$_project = self::create_class_object(org_openpsa_projects_project::class);
    }

    public function testHandler_create()
    {
        midcom::get()->auth->request_sudo('org.openpsa.projects');

        $data = $this->run_handler('org.openpsa.projects', ['project', 'new']);
        $this->assertEquals('project-new', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_read()
    {
        midcom::get()->auth->request_sudo('org.openpsa.projects');

        $data = $this->run_handler('org.openpsa.projects', ['project', self::$_project->guid]);
        $this->assertEquals('project',  $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_update()
    {
        midcom::get()->auth->request_sudo('org.openpsa.projects');

        $data = $this->run_handler('org.openpsa.projects', ['project', 'edit', self::$_project->guid]);
        $this->assertEquals('project-edit', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
