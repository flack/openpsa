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
class org_openpsa_contacts_handler_person_createTest extends openpsa_testcase
{
    protected static $_person;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_create()
    {
        midcom::get()->auth->request_sudo('org.openpsa.contacts');

        $data = $this->run_handler('org.openpsa.contacts', ['person', 'create']);
        $this->assertEquals('person_new', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_create_group()
    {
        midcom::get()->auth->request_sudo('org.openpsa.contacts');

        $group = $this->create_object(org_openpsa_contacts_group_dba::class);

        $data = $this->run_handler('org.openpsa.contacts', ['person', 'create', $group->guid]);
        $this->assertEquals('person_new_group', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
