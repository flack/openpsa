<?php
require_once('rootfile.php');

class accountTest extends openpsa_testcase
{
    protected static $_person;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_class_object('midcom_db_person');
    }

    public function testCRUD()
    {
        $_MIDCOM->auth->request_sudo('midcom.core');

        $account = midcom_core_account::get(self::$_person);
        $this->assertTrue($account instanceOf midcom_core_account);

        $password = 'password_' . time();
        $account->set_password($password);
        $this->assertEquals(midcom_connection::prepare_password($password), $account->get_password());

        $username = __CLASS__ . ' user ' . time();
        $account->set_username($username);
        $this->assertEquals($username, $account->get_username());

        $stat = $account->save();
        $this->assertTrue($stat);

        $new_username = __CLASS__ . ' user ' . time();
        $account->set_username($new_username);
        $stat = $account->save();
        $this->assertTrue($stat);
        $this->assertEquals($new_username, $account->get_username());

        $stat = $account->delete();
        $this->assertTrue($stat);

        $_MIDCOM->auth->drop_sudo();
    }

    public function testNameUnique()
    {
        $_MIDCOM->auth->request_sudo('midcom.core');

        $account1 = midcom_core_account::get(self::$_person);
        $username = __CLASS__ . ' user ' . time();
        $account1->set_username($username);
        $account1->save();
        $this->assertEquals($username, $account1->get_username());

        $person = $this->create_object('midcom_db_person');
        $account2 = midcom_core_account::get($person);

        $password = 'password_' . time();
        $account2->set_password($password);
        $account2->set_username($username);

        $stat = $account2->save();
        $this->assertFalse($stat);

        $_MIDCOM->auth->drop_sudo();
    }
}
?>