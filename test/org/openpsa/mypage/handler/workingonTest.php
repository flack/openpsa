<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\mypage\handler;

use openpsa_testcase;
use org_openpsa_projects_project;
use org_openpsa_projects_task_dba;
use org_openpsa_expenses_hour_report_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class workingonTest extends openpsa_testcase
{
    private static $task;

    public static function setUpBeforeClass() : void
    {
        self::create_user(true);
        $project = self::create_class_object(org_openpsa_projects_project::class);
        self::$task = self::create_class_object(org_openpsa_projects_task_dba::class, ['project' => $project->id]);
    }

    public function testHandler_workingon()
    {
        $this->create_object(org_openpsa_expenses_hour_report_dba::class, ['task' => self::$task->id]);
        $data = $this->run_handler('org.openpsa.mypage', ['workingon']);
        $this->assertEquals('workingon', $data['handler_id']);
    }

    public function testHandler_weekreview_redirect()
    {

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'task' => self::$task->id,
            'description' => 'test',
            'action' => 'stop',
        ];

        $url = $this->run_relocate_handler('org.openpsa.mypage', ['workingon', 'set']);
        $this->assertEquals('workingon/', $url);
    }
}
