<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\sales\handler;

use midcom_db_person;
use openpsa_testcase;
use midcom;
use org_openpsa_sales_salesproject_dba;
use org_openpsa_contacts_group_dba;
use org_openpsa_contacts_person_dba;
use midcom_db_member;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class editTest extends openpsa_testcase
{
    protected static midcom_db_person $_person;

    public static function setUpBeforeClass() : void
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_edit()
    {
        midcom::get()->auth->request_sudo('org.openpsa.sales');

        $salesproject = $this->create_object(org_openpsa_sales_salesproject_dba::class);

        $data = $this->run_handler('org.openpsa.sales', ['salesproject', 'edit', $salesproject->guid]);
        $this->assertEquals('salesproject_edit', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_new()
    {
        midcom::get()->auth->request_sudo('org.openpsa.sales');

        $data = $this->run_handler('org.openpsa.sales', ['salesproject', 'new']);
        $this->assertEquals('salesproject_new', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_new_customer()
    {
        midcom::get()->auth->request_sudo('org.openpsa.sales');

        $group = $this->create_object(org_openpsa_contacts_group_dba::class);
        $data = $this->run_handler('org.openpsa.sales', ['salesproject', 'new', $group->guid]);
        $this->assertEquals('salesproject_new_customer', $data['handler_id']);

        $person = $this->create_object(org_openpsa_contacts_person_dba::class);
        $data = $this->run_handler('org.openpsa.sales', ['salesproject', 'new', $person->guid]);
        $this->assertEquals('salesproject_new_customer', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_new_customer_from_contact()
    {
        midcom::get()->auth->request_sudo('org.openpsa.sales');

        $group = $this->create_object(org_openpsa_contacts_group_dba::class, [
            'official' => __FUNCTION__
        ]);
        $person = $this->create_object(org_openpsa_contacts_person_dba::class);
        $this->create_object(midcom_db_member::class, [
            'uid' => $person->id,
            'gid' => $group->id
        ]);

        $formdata = [
            'title' => __CLASS__ . '::' . __FUNCTION__,
            'customerContact' => [
                'selection' => '[' . $person->id . ']'
            ],
            'customer' => (string) $group->id
        ];

        $this->submit_dm_no_relocate_form('controller', $formdata, 'org.openpsa.sales', ['salesproject', 'new']);

        $qb = org_openpsa_sales_salesproject_dba::new_query_builder();
        $qb->add_constraint('title', '=', $formdata['title']);
        $results = $qb->execute();
        $this->register_objects($results);
        $this->assertCount(1, $results);
        $this->assertEquals($group->id, $results[0]->customer);
        $this->assertEquals($person->id, $results[0]->customerContact);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_customers()
    {
        midcom::get()->auth->request_sudo('org.openpsa.sales');

        $group = $this->create_object(org_openpsa_contacts_group_dba::class, [
            'official' => __FUNCTION__
        ]);
        $person = $this->create_object(org_openpsa_contacts_person_dba::class);
        $this->create_object(midcom_db_member::class, [
            'uid' => $person->id,
            'gid' => $group->id
        ]);

        $_GET = ['contact' => $person->id];
        $data = $this->run_handler('org.openpsa.sales', ['salesproject', 'customers']);
        $this->assertEquals('salesproject_customers', $data['handler_id']);

        $groups = json_decode($data['__openpsa_testcase_response']->getContent(), true);
        $this->assertEquals([['id' => $group->id, 'label' => $group->get_label()]], $groups);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_delete()
    {
        midcom::get()->auth->request_sudo('org.openpsa.sales');

        $salesproject = $this->create_object(org_openpsa_sales_salesproject_dba::class);

        $data = $this->run_handler('org.openpsa.sales', ['salesproject', 'delete', $salesproject->guid]);
        $this->assertEquals('salesproject_delete', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
