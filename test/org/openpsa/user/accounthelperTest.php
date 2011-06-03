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
class org_openpsa_user_accounthelperTest extends openpsa_testcase
{
    protected static $_user;

    public static function setUpBeforeClass()
    {
        self::$_user = self::create_user();
    }

    public function testGenerate_password()
    {
        $password = org_openpsa_user_accounthelper::generate_password();
        $this->assertTrue(is_string($password));
        $this->assertEquals(8, strlen($password));

        $password = org_openpsa_user_accounthelper::generate_password(16);
        $this->assertEquals(16, strlen($password));
    }

    public function testCheck_password_strength()
    {
        $accounthelper = new org_openpsa_user_accounthelper;
        $this->assertFalse($accounthelper->check_password_strength(''));
        $this->assertFalse($accounthelper->check_password_strength('abcabcabcabcabcabc'));
        $password = $accounthelper->generate_safe_password();
        $this->assertTrue($accounthelper->check_password_strength($password));
    }

    /**
     * @depends testCheck_password_strength
     */
    public function testGenerate_safe_password()
    {
        $accounthelper = new org_openpsa_user_accounthelper;
        $password = $accounthelper->generate_safe_password();
        $this->assertTrue(is_string($password));
        $this->assertEquals(8, strlen($password));
        $this->assertTrue($accounthelper->check_password_strength($password));
    }

    /**
     * @depends testGenerate_safe_password
     */
    public function testCheck_password_reuse()
    {
        $accounthelper = new org_openpsa_user_accounthelper(self::$_user);
        $account = midcom_core_account::get(self::$_user);
        $password = $account->get_password();
        $this->assertFalse($accounthelper->check_password_reuse($password));

        do
        {
            $password1 = $accounthelper->generate_safe_password();
        } while ($password === $password1);
        do
        {
            $password2 = $accounthelper->generate_safe_password();
        } while ($password === $password2 || $password1 === $password2);
        do
        {
            $password3 = $accounthelper->generate_safe_password();
        } while ($password3 === $password || $password3 === $password1 || $password3 === $password2);
        $old_passwords = array($password1, $password2);
        self::$_user->set_parameter('org_openpsa_user_password', 'old_passwords', serialize($old_passwords));

        $this->assertFalse($accounthelper->check_password_reuse($password1));
        $this->assertTrue($accounthelper->check_password_reuse($password3));
    }

    public function testCheck_password_age()
    {
        $accounthelper = new org_openpsa_user_accounthelper(self::$_user);
        $this->assertFalse($accounthelper->check_password_age());
        self::$_user->set_parameter('org_openpsa_user_password', 'last_change', time());
        $this->assertTrue($accounthelper->check_password_age());
        self::$_user->set_parameter('org_openpsa_user_password', 'last_change', (time() - 60 * 60 * 24 * 30 * 12));
        $this->assertFalse($accounthelper->check_password_age());
    }

    /**
     * @depends testGenerate_safe_password
     */
    public function testDisable_account()
    {
        $accounthelper = new org_openpsa_user_accounthelper(self::$_user);
        $account = midcom_core_account::get(self::$_user);
        $password = $account->get_password();

        $this->assertTrue($accounthelper->disable_account());
        $account = midcom_core_account::get(self::$_user);
        $this->assertEquals('', $account->get_password());
        $this->assertEquals($password, self::$_user->get_parameter('org_openpsa_user_blocked_account', 'account_password'));

        $args = array
        (
            'guid' => self::$_user->guid,
            'parameter_name' => 'org_openpsa_user_blocked_account',
            'password' => 'account_password',
        );

        $qb = midcom_services_at_entry_dba::new_query_builder();
        $qb->add_constraint('argumentsstore', '=', serialize($args));
        $result = $qb->execute();
        $this->assertEquals(1, sizeof($result));
        $this->register_object($result[0]);

        $account->set_password($accounthelper->generate_safe_password());
        $account->save();
    }

    /**
     * @depends testCheck_password_strength
     * @depends testCheck_password_reuse
     * @depends testGenerate_safe_password
     */
    public function testSet_account()
    {
        $accounthelper = new org_openpsa_user_accounthelper(self::$_user);
        $account = midcom_core_account::get(self::$_user);
        $password = $account->get_password();
        $username = $account->get_username();

        self::$_user->delete_parameter('org_openpsa_user_password', 'old_passwords');
        self::$_user->delete_parameter('org_openpsa_user_password', 'last_change');
        do
        {
            $new_password = $accounthelper->generate_safe_password();
        } while ($password === $new_password);

        $new_username = $username . time();

        $this->assertTrue($accounthelper->set_account($new_username, $new_password));
        $this->assertEquals(midcom_connection::prepare_password($new_password), $account->get_password());
        $this->assertEquals($new_username, $account->get_username());
        $this->assertFalse(is_null(self::$_user->get_parameter('org_openpsa_user_password', 'last_change')));
        $this->assertEquals(serialize(array($password)), self::$_user->get_parameter('org_openpsa_user_password', 'old_passwords'));
    }

}
?>