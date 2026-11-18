<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\relatedto\finder;

use openpsa_testcase;
use midcom;
use midcom_db_person;
use org_openpsa_relatedto_dba;
use org_openpsa_projects_task_dba;
use org_openpsa_calendar_event_dba;
use org_openpsa_calendar_event_member_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class eventTest extends openpsa_testcase
{
    private org_openpsa_projects_task_dba $task;
    private array $participants;

    public function setUp() : void
    {
        $p1 = $this->create_object(midcom_db_person::class);
        $p2 = $this->create_object(midcom_db_person::class);
        $project = $this->create_object(\org_openpsa_projects_project::class);
        $this->task = $this->create_object(org_openpsa_projects_task_dba::class, [
            'project' => $project->id,
            'manager' => $p1->id,
            'start' => time() - 86400,
            'end' => time() + 86400,
        ]);
        $this->create_object(\org_openpsa_projects_task_resource_dba::class, [
            'task' => $this->task->id,
            'person' => $p2->id,
            'orgOpenpsaObtype' => \org_openpsa_projects_task_resource_dba::RESOURCE
        ]);
        $this->participants = [$p1->id => true, $p2->id => true];
    }

    private function create_event() : org_openpsa_calendar_event_dba
    {
        return $this->create_object(org_openpsa_calendar_event_dba::class, [
            'up' => \org_openpsa_calendar_interface::find_root_event()->id,
            'start' => time(),
            'end' => time() + 3600,
            'participants' => $this->participants,
        ]);
    }

    private function get_links(org_openpsa_calendar_event_dba $event) : array
    {
        $qb = org_openpsa_relatedto_dba::new_query_builder();
        $qb->add_constraint('fromGuid', '=', $event->guid);
        $qb->add_constraint('toGuid', '=', $this->task->guid);
        return $qb->execute();
    }

    public function testDuplicate_on_update()
    {
        midcom::get()->auth->request_sudo('org.openpsa.relatedto');
        $event = $this->create_event();
        $links = $this->get_links($event);
        $this->assertCount(1, $links);
        $this->register_object($links[0]);
        $this->assertEquals(org_openpsa_relatedto_dba::SUSPECTED, $links[0]->status);

        // Like a second CalDAV PUT: members are in the DB, finder runs on update
        foreach (array_keys($this->participants) as $uid) {
            $this->create_object(org_openpsa_calendar_event_member_dba::class, [
                'eid' => $event->id,
                'uid' => $uid,
                'notify_person' => false
            ]);
        }
        $event = new org_openpsa_calendar_event_dba($event->id);
        $event->send_notify = false;
        $this->assertTrue($event->update());
        $this->assertCount(1, $this->get_links($event));
        midcom::get()->auth->drop_sudo();
    }

    public function testDuplicate_with_request_param()
    {
        midcom::get()->auth->request_sudo('org.openpsa.relatedto');
        $_REQUEST['org_openpsa_relatedto'] = [[
            'toGuid' => $this->task->guid,
            'toClass' => org_openpsa_projects_task_dba::class,
            'toComponent' => 'org.openpsa.projects',
            'fromComponent' => 'org.openpsa.calendar',
            'status' => org_openpsa_relatedto_dba::CONFIRMED,
        ]];
        try {
            $event = $this->create_event();
        } finally {
            unset($_REQUEST['org_openpsa_relatedto']);
        }
        $links = $this->get_links($event);
        $this->assertCount(1, $links);
        $this->register_object($links[0]);
        $this->assertEquals(org_openpsa_relatedto_dba::CONFIRMED, $links[0]->status);
        midcom::get()->auth->drop_sudo();
    }
}
