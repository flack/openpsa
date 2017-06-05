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
class org_openpsa_user_handler_person_accountTest extends openpsa_testcase
{
    protected static $_user;

    public static function setUpBeforeClass()
    {
        self::$_user = self::create_user(true);
    }

    public function test_handler_create()
    {
        midcom::get()->auth->request_sudo('org.openpsa.user');

        $person = $this->create_object('midcom_db_person');

        $data = $this->run_handler('org.openpsa.user', ['account', 'create', $person->guid]);
        $this->assertEquals('account_create', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function test_handler_edit()
    {
        midcom::get()->auth->request_sudo('org.openpsa.user');

        $person = $this->create_object('midcom_db_person');

        $data = $this->run_handler('org.openpsa.user', ['account', 'edit', self::$_user->guid]);
        $this->assertEquals('account_edit', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function test_handler_delete()
    {
        midcom::get()->auth->request_sudo('org.openpsa.user');

        $person = $this->create_object('midcom_db_person');

        $data = $this->run_handler('org.openpsa.user', ['account', 'delete', self::$_user->guid]);
        $this->assertEquals('account_delete', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
