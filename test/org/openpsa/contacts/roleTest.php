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
class org_openpsa_contacts_roleTest extends openpsa_testcase
{
    public function testCRUD()
    {
        $salesproject = $this->create_object('org_openpsa_sales_salesproject_dba');
        $person1 = $this->create_object('midcom_db_person');
        $person2 = $this->create_object('midcom_db_person');

        midcom::get()->auth->request_sudo('org.openpsa.contacts');

        $member1 = new org_openpsa_contacts_role_dba();
        $member1->objectGuid = $salesproject->guid;
        $member1->person = $person1->id;
        $member1->role = org_openpsa_sales_salesproject_dba::ROLE_MEMBER;
        $stat = $member1->create();
        $this->assertTrue($stat);
        $this->register_object($member1);
        $this->assertEquals(org_openpsa_sales_salesproject_dba::ROLE_MEMBER, $member1->role);
        $this->assertEquals(array($person1->id => true), $salesproject->contacts);

        $stat = $member1->delete();
        $this->assertTrue($stat);

        $stat = org_openpsa_contacts_role_dba::add($salesproject->guid, $person2->id, org_openpsa_sales_salesproject_dba::ROLE_MEMBER);
        $this->assertTrue($stat);

        $qb = org_openpsa_contacts_role_dba::new_query_builder();
        $qb->add_constraint('objectGuid', '=', $salesproject->guid);
        $this->assertEquals(1, $qb->count());

        $members = $qb->execute();
        $member2 = $members[0];
        $this->assertEquals(org_openpsa_sales_salesproject_dba::ROLE_MEMBER, $member2->role);
        $this->assertEquals($person2->id, $member2->person);

        $salesproject->refresh();
        $this->assertEquals(array($person2->id => true), $salesproject->contacts);

        $stat = $member2->delete();
        $this->assertTrue($stat);

        midcom::get()->auth->drop_sudo();
    }
}
