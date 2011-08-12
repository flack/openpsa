<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . DIRECTORY_SEPARATOR);
    require_once(OPENPSA_TEST_ROOT . 'rootfile.php');
}

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

        self::$_project = self::create_class_object('org_openpsa_projects_project');
        self::$_task = self::create_class_object('org_openpsa_projects_task_dba', array('project' => self::$_project->id));
    }

    public function testHandler_create()
    {
        midcom::get('auth')->request_sudo('org.openpsa.projects');

        $data = $this->run_handler('org.openpsa.projects', array('task', 'new'));
        $this->assertEquals('task-new', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_create2()
    {
        midcom::get('auth')->request_sudo('org.openpsa.projects');

        $data = $this->run_handler('org.openpsa.projects', array('task', 'new', 'project', self::$_project->guid));
        $this->assertEquals('task-new-2', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_read()
    {
        midcom::get('auth')->request_sudo('org.openpsa.projects');

        $data = $this->run_handler('org.openpsa.projects', array('task', self::$_task->guid));
        $this->assertEquals('task_view',  $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_update()
    {
        midcom::get('auth')->request_sudo('org.openpsa.projects');

        $data = $this->run_handler('org.openpsa.projects', array('task', 'edit', self::$_task->guid));
        $this->assertEquals('task_edit', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_delete()
    {
        midcom::get('auth')->request_sudo('org.openpsa.projects');

        $url = $this->run_relocate_handler('org.openpsa.projects', array('task', self::$_task->guid, 'complete'));
        $this->assertEquals('task/' . self::$_task->guid . '/', $url);

        midcom::get('auth')->drop_sudo();
    }
}
?>