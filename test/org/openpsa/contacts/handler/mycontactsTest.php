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

    public static function setUpBeforeClass() : void
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_list()
    {
        midcom::get()->auth->request_sudo('org.openpsa.contacts');

        $data = $this->run_handler('org.openpsa.contacts', ['mycontacts']);
        $this->assertEquals('mycontacts', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_add()
    {
        midcom::get()->auth->request_sudo('org.openpsa.contacts');

        $person = $this->create_object(org_openpsa_contacts_person_dba::class);

        $url = $this->run_relocate_handler('org.openpsa.contacts', ['mycontacts', 'add', $person->guid]);
        $this->assertEquals('person/' . $person->guid . '/', $url);

        $param = unserialize(self::$_person->get_parameter('org.openpsa.contacts', 'mycontacts'));
        $this->assertEquals([$person->guid], $param);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_remove()
    {
        midcom::get()->auth->request_sudo('org.openpsa.contacts');
        self::$_person->delete_parameter('org.openpsa.contacts', 'mycontacts');

        $person = $this->create_object(org_openpsa_contacts_person_dba::class);

        $url = $this->run_relocate_handler('org.openpsa.contacts', ['mycontacts', 'add', $person->guid]);
        $this->assertEquals('person/' . $person->guid . '/', $url);

        $url = $this->run_relocate_handler('org.openpsa.contacts', ['mycontacts', 'remove', $person->guid]);
        $this->assertEquals('person/' . $person->guid . '/', $url);

        $param = unserialize(self::$_person->get_parameter('org.openpsa.contacts', 'mycontacts'));
        $this->assertEquals([], $param);

        midcom::get()->auth->drop_sudo();
    }
}
