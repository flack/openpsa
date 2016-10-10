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
class org_openpsa_contacts_duplicates_mergeTest extends openpsa_testcase
{
    public function test_person_merge()
    {
        $person1 = $this->create_object('midcom_db_person');
        $person2 = $this->create_object('midcom_db_person');
        $group = $this->create_object('midcom_db_group');
        $this->create_object('midcom_db_member', array('uid' => $person1->id, 'gid' => $group->id));
        $this->create_object('midcom_db_member', array('uid' => $person2->id, 'gid' => $group->id));
        $config = midcom_baseclasses_components_configuration::get('org.openpsa.contacts', 'config');
        $merger = new org_openpsa_contacts_duplicates_merge('person', $config);

        midcom::get()->auth->request_sudo('org.openpsa.contacts');
        $stat = $merger->merge($person1, $person2, 'all');
        midcom::get()->auth->drop_sudo();
        $this->assertTrue($stat);
        $qb = midcom_db_member::new_query_builder();
        $qb->add_constraint('gid', '=', $group->id);
        $this->assertEquals(1, $qb->count());
    }

    public function test_person_merge_delete()
    {
        $person1 = $this->create_object('midcom_db_person');
        $person2 = $this->create_object('midcom_db_person');
        $event = $this->create_object('org_openpsa_calendar_event_dba');
        $this->create_object('midcom_db_eventmember', array('uid' => $person1->id, 'eid' => $event->id));
        $this->create_object('midcom_db_eventmember', array('uid' => $person2->id, 'eid' => $event->id));
        $config = midcom_baseclasses_components_configuration::get('org.openpsa.contacts', 'config');
        $merger = new org_openpsa_contacts_duplicates_merge('person', $config);

        midcom::get()->auth->request_sudo('org.openpsa.contacts');
        $stat = $merger->merge_delete($person1, $person2);
        midcom::get()->auth->drop_sudo();
        $this->assertTrue($stat);
        $qb = midcom_db_eventmember::new_query_builder();
        $qb->add_constraint('eid', '=', $event->id);
        $this->assertEquals(1, $qb->count());

        $qb = midcom_db_person::new_query_builder();
        $qb->add_constraint('id', '=', $person2->id);
        $this->assertEquals(0, $qb->count());
    }
}