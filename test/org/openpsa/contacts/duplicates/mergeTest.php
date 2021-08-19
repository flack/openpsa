<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\contacts\duplicates;

use openpsa_testcase;
use midcom;
use midcom_db_person;
use midcom_db_group;
use midcom_db_member;
use midcom_baseclasses_components_configuration;
use org_openpsa_contacts_duplicates_merge;
use org_openpsa_calendar_event_dba;
use org_openpsa_calendar_event_member_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class mergeTest extends openpsa_testcase
{
    public function test_person_merge()
    {
        $person1 = $this->create_object(midcom_db_person::class);
        $person2 = $this->create_object(midcom_db_person::class);
        $group = $this->create_object(midcom_db_group::class);
        $this->create_object(midcom_db_member::class, ['uid' => $person1->id, 'gid' => $group->id]);
        $this->create_object(midcom_db_member::class, ['uid' => $person2->id, 'gid' => $group->id]);
        $config = midcom_baseclasses_components_configuration::get('org.openpsa.contacts', 'config');
        $merger = new org_openpsa_contacts_duplicates_merge('person', $config);

        midcom::get()->auth->request_sudo('org.openpsa.contacts');
        $merger->merge($person1, $person2, 'all');
        midcom::get()->auth->drop_sudo();
        $qb = midcom_db_member::new_query_builder();
        $qb->add_constraint('gid', '=', $group->id);
        $this->assertEquals(1, $qb->count());
    }

    public function test_person_merge_delete()
    {
        $person1 = $this->create_object(midcom_db_person::class);
        $person2 = $this->create_object(midcom_db_person::class);
        $event = $this->create_object(org_openpsa_calendar_event_dba::class, [
            'start' => time() - 60 * 60,
            'end' => time() + 60 * 60
        ]);
        $this->create_object(org_openpsa_calendar_event_member_dba::class, ['uid' => $person1->id, 'eid' => $event->id]);
        $this->create_object(org_openpsa_calendar_event_member_dba::class, ['uid' => $person2->id, 'eid' => $event->id]);
        $config = midcom_baseclasses_components_configuration::get('org.openpsa.contacts', 'config');
        $merger = new org_openpsa_contacts_duplicates_merge('person', $config);

        midcom::get()->auth->request_sudo('org.openpsa.contacts');
        $merger->merge_delete($person1, $person2);
        midcom::get()->auth->drop_sudo();
        $qb = org_openpsa_calendar_event_member_dba::new_query_builder();
        $qb->add_constraint('eid', '=', $event->id);
        $this->assertEquals(1, $qb->count());

        $qb = midcom_db_person::new_query_builder();
        $qb->add_constraint('id', '=', $person2->id);
        $this->assertEquals(0, $qb->count());
    }
}
