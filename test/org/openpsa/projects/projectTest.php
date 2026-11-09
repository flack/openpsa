<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\projects;

use openpsa_testcase;
use PHPUnit\Framework\Attributes\DataProvider;
use midcom;
use org_openpsa_projects_project;
use org_openpsa_projects_task_dba;
use org_openpsa_projects_task_status_dba as status;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class projectTest extends openpsa_testcase
{
    public function testCRUD()
    {
        midcom::get()->auth->request_sudo('org.openpsa.projects');

        $project = new org_openpsa_projects_project();
        $project->_use_rcs = false;

        $stat = $project->create();
        $this->assertTrue($stat);
        $this->register_object($project);

        $project->refresh();
        $this->assertEquals('Project #' . $project->id, $project->title);
        $project->title = 'Test Project';
        $stat = $project->update();
        $this->assertTrue($stat);
        $this->assertEquals('Test Project', $project->title);

        $stat = $project->delete();
        $this->assertTrue($stat);

        midcom::get()->auth->drop_sudo();
    }

    #[DataProvider('provider_refresh_from_tasks')]
    public function test_refresh_from_tasks(int $project_status, array $task_statuses, int $expected)
    {
        $project = $this->create_object(org_openpsa_projects_project::class, ['status' => $project_status]);
        foreach ($task_statuses as $status) {
            $this->create_object(org_openpsa_projects_task_dba::class, [
                'project' => $project->id,
                'status' => $status
            ]);
        }

        $this->sudo($project->refresh_from_tasks(...));

        $this->assertEquals($expected, $project->status);
    }

    public static function provider_refresh_from_tasks() : array
    {
        return [
            'on hold, started + accepted + completed' => [
                status::ONHOLD,
                [status::STARTED, status::ACCEPTED, status::COMPLETED],
                status::STARTED
            ],
            'on hold, started + reopened' => [
                status::ONHOLD,
                [status::STARTED, status::REOPENED],
                status::STARTED
            ],
            'on hold, started + completed' => [
                status::ONHOLD,
                [status::STARTED, status::COMPLETED],
                status::STARTED
            ],
            'ongoing, accepted + completed' => [
                status::STARTED,
                [status::ACCEPTED, status::COMPLETED],
                status::ONHOLD
            ],
        ];
    }
}
