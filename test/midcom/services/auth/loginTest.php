<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR);
    require_once(OPENPSA_TEST_ROOT . 'rootfile.php');
}

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_services_auth_loginTest extends openpsa_testcase
{
    protected static $_person;
    protected static $_password;
    protected static $_username;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_class_object('midcom_db_person');
        self::$_password = substr('p_' . time(), 0, 11);
        self::$_username = __CLASS__ . ' user ' . time();

        midcom::get('auth')->request_sudo('midcom.core');
        $account = midcom_core_account::get(self::$_person);
        $account->set_password(self::$_password);
        $account->set_username(self::$_username);
        $account->save();
        midcom::get('auth')->drop_sudo();
    }

    public function testLogin()
    {
        $auth = midcom::get('auth');
        $stat = $auth->login(self::$_username, self::$_password);
        $this->assertTrue($stat);

        $auth->_sync_user_with_backend();
        $this->assertTrue($auth->is_valid_user());

        $user = $auth->user;
        $this->assertTrue($user instanceof midcom_core_user);
        $this->assertEquals(self::$_person->guid, $user->guid);
        $this->assertEquals(self::$_person->id, midcom_connection::get_user());

        $auth->logout();
        $this->assertTrue(is_null($auth->user));
        $this->assertFalse($auth->is_valid_user());
    }
}
?>