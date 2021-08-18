<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\projects;

use openpsa_testcase;
use midcom;
use org_openpsa_sales_salesproject_dba;
use midcom_db_person;
use org_openpsa_projects_role_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class roleTest extends openpsa_testcase
{
    public function testCRUD()
    {
        $salesproject = $this->create_object(org_openpsa_sales_salesproject_dba::class);
        $person1 = $this->create_object(midcom_db_person::class);
        $person2 = $this->create_object(midcom_db_person::class);

        midcom::get()->auth->request_sudo('org.openpsa.contacts');

        $member1 = new org_openpsa_projects_role_dba();
        $member1->project = $salesproject->id;
        $member1->person = $person1->id;
        $member1->role = org_openpsa_sales_salesproject_dba::ROLE_MEMBER;
        $stat = $member1->create();
        $this->assertTrue($stat);
        $this->register_object($member1);
        $this->assertEquals(org_openpsa_sales_salesproject_dba::ROLE_MEMBER, $member1->role);
        $this->assertEquals([$person1->id => true], $salesproject->contacts);

        $stat = $member1->delete();
        $this->assertTrue($stat);

        $stat = org_openpsa_projects_role_dba::add($salesproject->id, $person2->id, org_openpsa_sales_salesproject_dba::ROLE_MEMBER);
        $this->assertTrue($stat);

        $qb = org_openpsa_projects_role_dba::new_query_builder();
        $qb->add_constraint('project', '=', $salesproject->id);
        $this->assertEquals(1, $qb->count());

        $members = $qb->execute();
        $member2 = $members[0];
        $this->assertEquals(org_openpsa_sales_salesproject_dba::ROLE_MEMBER, $member2->role);
        $this->assertEquals($person2->id, $member2->person);

        $salesproject->refresh();
        $this->assertEquals([$person2->id => true], $salesproject->contacts);

        $stat = $member2->delete();
        $this->assertTrue($stat);

        midcom::get()->auth->drop_sudo();
    }
}
