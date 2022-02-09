<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\user\handler\person;

use openpsa_testcase;
use midcom;
use midcom_db_person;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class accountTest extends openpsa_testcase
{
    protected static $_user;

    public static function setUpBeforeClass() : void
    {
        self::$_user = self::create_user(true);
    }

    public function test_handler_create()
    {
        midcom::get()->auth->request_sudo('org.openpsa.user');

        $person = $this->create_object(midcom_db_person::class);

        $data = $this->run_handler('org.openpsa.user', ['account', 'create', $person->guid]);
        $this->assertEquals('account_create', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function test_handler_edit()
    {
        midcom::get()->auth->request_sudo('org.openpsa.user');

        $data = $this->run_handler('org.openpsa.user', ['account', 'edit', self::$_user->guid]);
        $this->assertEquals('account_edit', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function test_handler_delete()
    {
        midcom::get()->auth->request_sudo('org.openpsa.user');

        $data = $this->run_handler('org.openpsa.user', ['account', 'delete', self::$_user->guid]);
        $this->assertEquals('account_delete', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function test_handler_welcome_email()
    {
        midcom::get()->auth->request_sudo('org.openpsa.user');

        $data = $this->run_handler('org.openpsa.user', ['account', 'welcome', self::$_user->guid]);
        $this->assertEquals('account_welcome', $data['handler_id']);
        $this->assertCount(1, \org_openpsa_mail_backend_unittest::$mails);

        midcom::get()->auth->drop_sudo();
    }
}
