<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\projects\handler\task;

use midcom_db_person;
use openpsa_testcase;
use midcom;
use org_openpsa_projects_project;
use org_openpsa_projects_task_dba;
use org_openpsa_projects_task_resource_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class resourcingTest extends openpsa_testcase
{
    protected static midcom_db_person $_person;
    protected static org_openpsa_projects_task_dba $_task;

    public static function setUpBeforeClass() : void
    {
        self::$_person = self::create_user(true);
        $project = self::create_class_object(org_openpsa_projects_project::class);
        self::$_task = self::create_class_object(org_openpsa_projects_task_dba::class, [
            'project' => $project->id,
            'start' => time(),
            'end' => time() + 3600
        ]);
    }

    public function testHandler_resourcing()
    {
        midcom::get()->auth->request_sudo('org.openpsa.projects');

        $data = $this->run_handler('org.openpsa.projects', ['task', 'resourcing', self::$_task->guid]);
        $this->assertEquals('task_resourcing', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_list_prospects()
    {
        midcom::get()->auth->request_sudo('org.openpsa.projects');

        $data = $this->run_handler('org.openpsa.projects', ['task', 'resourcing', 'prospects', self::$_task->guid]);
        $this->assertEquals('task_resourcing_prospects', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_prospect_slots()
    {
        midcom::get()->auth->request_sudo('org.openpsa.projects');

        $prospect = $this->create_object(org_openpsa_projects_task_resource_dba::class, [
            'task' => self::$_task->id,
            'person' => self::$_person->id,
            'orgOpenpsaObtype' => org_openpsa_projects_task_resource_dba::RESOURCE
        ]);

        $data = $this->run_handler('org.openpsa.projects', ['task', 'resourcing', 'prospect', $prospect->guid]);
        $this->assertEquals('task_resourcing_prospect_slots', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
