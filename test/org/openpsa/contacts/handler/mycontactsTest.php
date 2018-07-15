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
class org_openpsa_contacts_handler_mycontactsTest extends openpsa_testcase
{
    protected static $_person;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_list()
    {
        midcom::get()->auth->request_sudo('org.openpsa.contacts');

        $data = $this->run_handler('org.openpsa.contacts', ['mycontacts']);
        $this->assertEquals('mycontacts', $data['handler_id']);

        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_add()
    {
        midcom::get()->auth->request_sudo('org.openpsa.contacts');

        $person = $this->create_object(org_openpsa_contacts_person_dba::class);

        $url = $this->run_relocate_handler('org.openpsa.contacts', ['mycontacts', 'add', $person->guid]);
        $this->assertEquals('person/' . $person->guid . '/', $url);

        $qb = org_openpsa_contacts_list_dba::new_query_builder();
        $qb->add_constraint('person', '=', self::$_person->guid);
        $result = $qb->execute();
        $this->register_objects($result);
        $this->assertCount(1, $result, 'Contact list missing');

        $qb = midcom_db_member::new_query_builder();
        $qb->add_constraint('gid', '=', $result[0]->id);
        $qb->add_constraint('uid', '=', $person->id);
        $result = $qb->execute();
        $this->register_objects($result);
        $this->assertCount(1, $result);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_remove()
    {
        midcom::get()->auth->request_sudo('org.openpsa.contacts');

        $person = $this->create_object(org_openpsa_contacts_person_dba::class);

        $url = $this->run_relocate_handler('org.openpsa.contacts', ['mycontacts', 'add', $person->guid]);
        $this->assertEquals('person/' . $person->guid . '/', $url);


        $url = $this->run_relocate_handler('org.openpsa.contacts', ['mycontacts', 'remove', $person->guid]);
        $this->assertEquals('person/' . $person->guid . '/', $url);

        $qb = org_openpsa_contacts_list_dba::new_query_builder();
        $qb->add_constraint('person', '=', self::$_person->guid);
        $result = $qb->execute();
        $this->register_objects($result);
        $this->assertCount(1, $result, 'Contact list missing');

        midcom::get()->auth->drop_sudo();
    }
}
