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
class midcom_core_accountTest extends openpsa_testcase
{
    protected static $_person;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_class_object('midcom_db_person');
    }

    public function testCRUD()
    {
        midcom::get()->auth->request_sudo('midcom.core');

        $account = new midcom_core_account(self::$_person);
        $this->assertTrue($account instanceOf midcom_core_account);

        $password = 'password_' . time();
        $account->set_password($password);
        $this->assertTrue(midcom_connection::verify_password($password, $account->get_password()));

        $username = uniqid(__FUNCTION__ . ' user');
        $account->set_username($username);
        $this->assertEquals($username, $account->get_username());

        $stat = $account->save();
        $this->assertTrue($stat);

        $new_username = uniqid(__FUNCTION__ . ' user');
        $account->set_username($new_username);
        $stat = $account->save();
        $this->assertTrue($stat);
        $this->assertEquals($new_username, $account->get_username());

        $stat = $account->delete();
        $this->assertTrue($stat);

        midcom::get()->auth->drop_sudo();
    }

    public function testGet()
    {
        midcom::get()->auth->request_sudo('midcom.core');

        $account1 = new midcom_core_account(self::$_person);
        $username = uniqid(__FUNCTION__ . ' user');
        $account1->set_username($username);
        $stat = $account1->save();
        $this->assertTrue($stat);
        $stat = $account1->delete();
        $this->assertTrue($stat);

        // after deletion of account, try getting the account again
        // we should get a fresh object, not the one from the static cache
        $account2 = new midcom_core_account(self::$_person);
        $this->assertNotEquals(spl_object_hash($account1), spl_object_hash($account2), "We should get a fresh account object");
        // save and delete should work as well
        $account2->set_username($username);
        $stat = $account2->save();
        $this->assertTrue($stat);
        $stat = $account2->delete();
        $this->assertTrue($stat);

        midcom::get()->auth->drop_sudo();
    }

    public function testNameUnique()
    {
        midcom::get()->auth->request_sudo('midcom.core');

        $account1 = new midcom_core_account(self::$_person);
        $username = uniqid(__FUNCTION__ . ' user');

        $account1->set_username($username);
        $stat = $account1->save();
        $this->assertTrue($stat);

        $this->assertEquals($username, $account1->get_username());

        $person = $this->create_object('midcom_db_person');
        $account2 = new midcom_core_account($person);

        $password = 'password_' . time();
        $account2->set_password($password);
        $account2->set_username($username);

        // save should fail as the username is not unique
        $stat = $account2->save();
        $this->assertFalse($stat);

        midcom::get()->auth->drop_sudo();
    }
}
