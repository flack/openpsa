<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . DIRECTORY_SEPARATOR);
    require_once(OPENPSA_TEST_ROOT . 'rootfile.php');
}

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class org_openpsa_contacts_handler_buddy_listTest extends openpsa_testcase
{
    protected static $_person;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_list()
    {
        midcom::get('auth')->request_sudo('org.openpsa.contacts');

        $data = $this->run_handler('org.openpsa.contacts', array('buddylist'));
        $this->assertEquals('buddylist', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_list_xml()
    {
        midcom::get('auth')->request_sudo('org.openpsa.contacts');

        $data = $this->run_handler('org.openpsa.contacts', array('buddylist', 'xml'));
        $this->assertEquals('buddylist_xml', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_add()
    {
        midcom::get('auth')->request_sudo('org.openpsa.contacts');

        $person = $this->create_object('org_openpsa_contacts_person_dba');

        $url = $this->run_relocate_handler('org.openpsa.contacts', array('buddylist', 'add', $person->guid));
        $this->assertEquals('person/' . $person->guid . '/', $url);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_remove()
    {
        midcom::get('auth')->request_sudo('org.openpsa.contacts');

        $person = $this->create_object('org_openpsa_contacts_person_dba');

        $url = $this->run_relocate_handler('org.openpsa.contacts', array('buddylist', 'add', $person->guid));
        $this->assertEquals('person/' . $person->guid . '/', $url);


        $url = $this->run_relocate_handler('org.openpsa.contacts', array('buddylist', 'remove', $person->guid));
        $this->assertEquals('person/' . $person->guid . '/', $url);

        midcom::get('auth')->drop_sudo();
    }

}
?>