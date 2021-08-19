<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\services\auth;

use openpsa_testcase;
use midcom_db_person;
use midcom;
use midcom_core_account;
use midcom_core_user;
use midcom_connection;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class loginTest extends openpsa_testcase
{
    protected static $_person;
    protected static $_password;
    protected static $_username;

    public static function setUpBeforeClass() : void
    {
        self::$_person = self::create_class_object(midcom_db_person::class);
        self::$_password = substr('p_' . time(), 0, 11);
        self::$_username = __CLASS__ . ' user ' . time();

        midcom::get()->auth->request_sudo('midcom.core');
        $account = new midcom_core_account(self::$_person);
        $account->set_password(self::$_password);
        $account->set_username(self::$_username);
        $account->save();
        midcom::get()->auth->drop_sudo();
    }

    public function testLogin()
    {
        $auth = midcom::get()->auth;
        $stat = $auth->login(self::$_username, self::$_password);
        $this->assertTrue($stat);
        $this->assertTrue($auth->is_valid_user());

        $user = $auth->user;
        $this->assertInstanceOf(midcom_core_user::class, $user);
        $this->assertEquals(self::$_person->guid, $user->guid);
        $this->assertEquals(self::$_person->id, midcom_connection::get_user());

        $auth->logout();
        $this->assertNull($auth->user);
        $this->assertFalse($auth->is_valid_user());
    }
}
