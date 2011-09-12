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
class org_openpsa_expenses_handler_hours_listTest extends openpsa_testcase
{
    protected static $_task;

    public static function setUpBeforeClass()
    {
        $project = self::create_class_object('org_openpsa_projects_project');
        self::$_task = self::create_class_object('org_openpsa_projects_task_dba', array('project' => $project->id));
        self::create_user(true);
    }

    public function testHandler_list_hours_task()
    {
        midcom::get('auth')->request_sudo('org.openpsa.expenses');

        $data = $this->run_handler('org.openpsa.expenses', array('hours', 'task', self::$_task->guid));
        $this->assertEquals('list_hours_task', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_list_hours_task_all()
    {
        midcom::get('auth')->request_sudo('org.openpsa.expenses');

        $data = $this->run_handler('org.openpsa.expenses', array('hours', 'task', 'all', self::$_task->guid));
        $this->assertEquals('list_hours_task_all', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_list_hours_between()
    {
        midcom::get('auth')->request_sudo('org.openpsa.expenses');

        $data = $this->run_handler('org.openpsa.expenses', array('hours', 'between', '2011-10-03', '2011-10-10'));
        $this->assertEquals('list_hours_between', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_list_hours_between_all()
    {
        midcom::get('auth')->request_sudo('org.openpsa.expenses');

        $data = $this->run_handler('org.openpsa.expenses', array('hours', 'between', 'all', '2011-10-03', '2011-10-10'));
        $this->assertEquals('list_hours_between_all', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }
}
?>