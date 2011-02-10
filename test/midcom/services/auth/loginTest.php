<?php
require_once('rootfile.php');

class loginTest extends openpsa_testcase
{
    protected static $_person;
    protected static $_password;
    protected static $_username;

    public static function setUpBeforeClass()
    {
        self::$_person = new midcom_db_person();
        self::$_password = 'password_' . time();
        self::$_username = __CLASS__ . ' user ' . time();

        $_MIDCOM->auth->request_sudo('midcom.core');
        self::$_person->create();

        $account = midcom_core_account::get(self::$_person);
        $account->set_password(self::$_password);
        $account->set_username(self::$_username);
        $account->save();
        $_MIDCOM->auth->drop_sudo();
    }

    public function testLogin()
    {
        $stat = $_MIDCOM->auth->login(self::$_username, self::$_password);
        $this->assertTrue($stat);

        $_MIDCOM->auth->_sync_user_with_backend();
        $this->assertTrue($_MIDCOM->auth->is_valid_user());

        $user = $_MIDCOM->auth->user;
        $this->assertTrue($user instanceof midcom_core_user);
        $this->assertEquals(self::$_person->guid, $user->guid);
        $this->assertEquals(self::$_person->id, midcom_connection::get_user());

        $_MIDCOM->auth->logout();
        $this->assertTrue(is_null($_MIDCOM->auth->user));
        $this->assertFalse($_MIDCOM->auth->is_valid_user());
    }

    public static function tearDownAfterClass()
    {
        $_MIDCOM->auth->request_sudo('midcom.core');
        self::$_person->delete();
        $_MIDCOM->auth->drop_sudo();
    }
}
?>