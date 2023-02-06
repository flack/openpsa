<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\contacts\handler\group;

use midcom_db_person;
use openpsa_testcase;
use midcom;
use org_openpsa_contacts_group_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class viewTest extends openpsa_testcase
{
    protected static midcom_db_person $_person;
    protected static org_openpsa_contacts_group_dba $_group;

    public static function setUpBeforeClass() : void
    {
        self::$_person = self::create_user(true);
        self::$_group = self::create_class_object(org_openpsa_contacts_group_dba::class);
    }

    public function testHandler_view_group()
    {
        midcom::get()->auth->request_sudo('org.openpsa.contacts');

        $data = $this->run_handler('org.openpsa.contacts', ['group', self::$_group->guid]);
        $this->assertEquals('group_view', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_view_organization()
    {
        $attributes = [
            'orgOpenpsaObtype' => org_openpsa_contacts_group_dba::ORGANIZATION,
            'postalStreet' => 'test'
        ];
        $organization = $this->create_object(org_openpsa_contacts_group_dba::class, $attributes);
        midcom::get()->auth->request_sudo('org.openpsa.contacts');

        $data = $this->run_handler('org.openpsa.contacts', ['group', $organization->guid]);
        $this->assertEquals('group_view', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_json()
    {
        midcom::get()->auth->request_sudo('org.openpsa.contacts');

        $data = $this->run_handler('org.openpsa.contacts', ['group', 'json', self::$_group->guid]);
        $this->assertEquals('group_view_json', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
