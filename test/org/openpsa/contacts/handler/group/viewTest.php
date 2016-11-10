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
class org_openpsa_contacts_handler_group_viewTest extends openpsa_testcase
{
    protected static $_person;
    protected static $_group;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
        self::$_group = self::create_class_object('org_openpsa_contacts_group_dba');
    }

    public function testHandler_view_group()
    {
        midcom::get()->auth->request_sudo('org.openpsa.contacts');

        $data = $this->run_handler('org.openpsa.contacts', array('group', self::$_group->guid));
        $this->assertEquals('group_view', $data['handler_id']);

        $output = $this->show_handler($data);

        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_view_organization()
    {
        $attributes = array(
            'orgOpenpsaObtype' => org_openpsa_contacts_group_dba::ORGANIZATION
        );
        $organization = $this->create_object('org_openpsa_contacts_group_dba', $attributes);
        midcom::get()->auth->request_sudo('org.openpsa.contacts');

        $data = $this->run_handler('org.openpsa.contacts', array('group', $organization->guid));
        $this->assertEquals('group_view', $data['handler_id']);

        $output = $this->show_handler($data);

        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_json()
    {
        midcom::get()->auth->request_sudo('org.openpsa.contacts');

        $data = $this->run_handler('org.openpsa.contacts', array('group', 'json', self::$_group->guid));
        $this->assertEquals('group_view_json', $data['handler_id']);

        $output = $this->show_handler($data);

        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }
}
