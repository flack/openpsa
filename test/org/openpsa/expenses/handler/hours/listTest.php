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
class org_openpsa_expenses_handler_hours_listTest extends openpsa_testcase
{
    protected static $_task;

    public static function setUpBeforeClass()
    {
        $project = self::create_class_object(org_openpsa_projects_project::class);
        self::$_task = self::create_class_object(org_openpsa_projects_task_dba::class, ['project' => $project->id]);
        self::create_user(true);
    }

    public function testHandler_list_hours_task()
    {
        midcom::get()->auth->request_sudo('org.openpsa.expenses');

        $data = $this->run_handler('org.openpsa.expenses', ['hours', 'task', self::$_task->guid]);
        $this->assertEquals('list_hours_task', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_list_hours()
    {
        midcom::get()->auth->request_sudo('org.openpsa.expenses');

        $_GET = [
            'date' => [
                'from' => '2011-10-03',
                'to' => '2011-10-10'
            ]
        ];

        $data = $this->run_handler('org.openpsa.expenses', ['hours']);
        $this->assertEquals('list_hours', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
