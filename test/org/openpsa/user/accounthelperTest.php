<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\user;

use openpsa_testcase;
use midcom_core_account;
use midcom;
use org_openpsa_user_accounthelper;
use midcom_db_person;
use Exception;
use midcom_baseclasses_components_configuration;
use midcom_services_at_entry_dba;
use midcom_connection;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class accounthelperTest extends openpsa_testcase
{
    protected static $_user;

    public static function setUpBeforeClass() : void
    {
        self::$_user = self::create_user();
    }

    public function testCreate_account()
    {
        midcom::get()->auth->request_sudo("midcom.core");

        $helper = new org_openpsa_user_accounthelper;
        // test error cases
        $person = self::create_class_object(midcom_db_person::class, []);
        // no person guid
        $this->assertFalse($helper->create_account("", "", ""));
        // no username
        $this->assertFalse($helper->create_account($person->guid, "", ""));
        // cannot send welcome mail without mail address
        $this->assertFalse($helper->create_account($person->guid, uniqid(__FUNCTION__ . "Bob"), "", "", true));

        // test with no password given
        $person = self::create_class_object(midcom_db_person::class, []);
        $this->assertTrue($helper->create_account($person->guid, uniqid(__FUNCTION__ . "Alice"), "", "", false, false), $helper->errstr);

        // this should work, so creating an account again should fail
        $this->assertFalse($helper->create_account($person->guid, uniqid(__FUNCTION__ . "Alice"), ""));

        // test with password given
        $person = self::create_class_object(midcom_db_person::class, []);
        $helper = new org_openpsa_user_accounthelper();
        $password = $helper->generate_safe_password();

        $this->assertTrue($helper->create_account($person->guid, uniqid(__FUNCTION__ . "Bob"), "bob@nowhere.cc", $password, false, false), $helper->errstr);

        midcom::get()->auth->drop_sudo();
    }

    public function testDelete_account()
    {
        midcom::get()->auth->request_sudo("midcom.core");

        $person = self::create_user();
        $helper = new org_openpsa_user_accounthelper($person);
        $this->assertTrue($helper->delete_account());

        midcom::get()->auth->drop_sudo();
    }

    public function testClose_account()
    {
        midcom::get()->auth->request_sudo("midcom.core");

        $person = self::create_user();
        $helper = new org_openpsa_user_accounthelper($person);

        $account = new midcom_core_account($person);
        $password = $account->get_password();

        // not blocked yet
        $this->assertFalse($helper->is_blocked());
        $this->assertTrue($helper->close_account());
        $account_after = new midcom_core_account($person);

        // check that account password is empty and the parameter is set correctly
        $this->assertEmpty($account_after->get_password());
        $param = $person->get_parameter("org_openpsa_user_blocked_account", "account_password");
        $this->assertEquals($password, $param);

        // test that account is blocked
        $this->assertTrue($helper->is_blocked());

        // try closing again.. this should just return true
        $this->assertTrue($helper->close_account());

        midcom::get()->auth->drop_sudo();
    }

    public function testReopen_account()
    {
        midcom::get()->auth->request_sudo("midcom.core");

        $person = self::create_user();
        $helper = new org_openpsa_user_accounthelper($person);

        $account = new midcom_core_account($person);
        $password = $account->get_password();

        // close account
        $this->assertFalse($helper->is_blocked());
        $this->assertTrue($helper->close_account());
        $this->assertTrue($helper->is_blocked());
        $account_blocked = new midcom_core_account($person);
        $this->assertEmpty($account_blocked->get_password());

        // now try reopening it
        $helper->reopen_account();
        $account_after = new midcom_core_account($person);

        // check that account password is set again and the parameter is deleted
        $this->assertEquals($password, $account_after->get_password(), "Password should be set again");
        $this->assertNull($person->get_parameter("org_openpsa_user_blocked_account", "account_password"), "Param should have been deleted");

        // account is not blocked anymore
        $this->assertFalse($helper->is_blocked());

        // try reopening unblocked account
        try {
            $helper->reopen_account();
            $this->fail("Reopening an unblocked account should throw an exception");
        } catch (Exception $e) {
        }

        midcom::get()->auth->drop_sudo();
    }

    private function _get_person_by_formdata($data, $expected_result)
    {
        $person = org_openpsa_user_accounthelper::get_person_by_formdata($data);
        if ($expected_result) {
            $this->assertInstanceOf(midcom_db_person::class, $person);
        } else {
            $this->assertFalse($person);
        }
        $this->reset_server_vars();
    }

    public function testGet_person_by_formdata()
    {
        midcom::get()->auth->request_sudo("midcom.core");

        // try invalid data
        $this->_get_person_by_formdata([], false);

        // try invalid username
        $fake_username = uniqid("abcabcab");
        $this->_get_person_by_formdata(["username" => $fake_username, "password" => "abc"], false);

        // try valid username
        $username = uniqid("PBF");
        $person = self::create_user();
        $account = new midcom_core_account($person);
        $account->set_username($username);
        $account->save();

        $this->_get_person_by_formdata(["username" => $username, "password" => "abc"], true);

        midcom::get()->auth->drop_sudo();
    }

    public function testCheck_login_attempts()
    {
        midcom::get()->auth->request_sudo("midcom.core");

        $person = self::create_user();
        $helper = new org_openpsa_user_accounthelper($person);

        $max_attempts = midcom_baseclasses_components_configuration::get("org.openpsa.user", 'config')->get('max_password_attempts');

        // no attempts so far..
        $this->assertFalse($helper->is_blocked(), 'account should not be blocked');
        $this->assertNull($person->get_parameter("org_openpsa_user_password", "attempts"));

        for ($attempt_num = 1; $attempt_num < $max_attempts; $attempt_num++) {
            $this->assertTrue($helper->check_login_attempts());
            $attempts = unserialize($person->get_parameter("org_openpsa_user_password", "attempts"));
            $this->assertCount($attempt_num, $attempts);
        }

        $this->assertFalse($helper->check_login_attempts());
        $this->assertTrue($helper->is_blocked(), 'account should get blocked now!');

        midcom::get()->auth->drop_sudo();
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
        $this->assertIsString($password);
        $this->assertEquals(8, strlen($password));
        $this->assertTrue($accounthelper->check_password_strength($password));
    }

    /**
     * @depends testGenerate_safe_password
     */
    public function testCheck_password_reuse()
    {
        $account = new midcom_core_account(self::$_user);
        $accounthelper = new org_openpsa_user_accounthelper(self::$_user);
        $password = self::$_user->extra;
        $this->assertFalse($accounthelper->check_password_reuse($password));

        do {
            $password1 = $accounthelper->generate_safe_password();
        } while ($password === $password1);
        do {
            $password2 = $accounthelper->generate_safe_password();
        } while (in_array($password2, [$password, $password1], true));

        // populate old_passwords
        midcom::get()->auth->request_sudo('org.openpsa.user');
        $this->assertTrue($accounthelper->set_account($account->get_username(), $password1));
        midcom::get()->auth->drop_sudo();

        $this->assertFalse($accounthelper->check_password_reuse($password));
        $this->assertTrue($accounthelper->check_password_reuse($password2));
    }

    public function testCheck_password_age()
    {
        $accounthelper = new org_openpsa_user_accounthelper(self::$_user);

        midcom::get()->auth->request_sudo('org.openpsa.user');
        self::$_user->set_parameter('org_openpsa_user_password', 'last_change', time());
        $this->assertTrue($accounthelper->check_password_age());
        self::$_user->delete_parameter('org_openpsa_user_password', 'last_change');
        $this->assertFalse($accounthelper->check_password_age());
        self::$_user->set_parameter('org_openpsa_user_password', 'last_change', (time() - 60 * 60 * 24 * 30 * 12));
        midcom::get()->auth->drop_sudo();
        $this->assertFalse($accounthelper->check_password_age());
    }

    /**
     * @depends testGenerate_safe_password
     */
    public function testDisable_account()
    {
        $accounthelper = new org_openpsa_user_accounthelper(self::$_user);
        $account = new midcom_core_account(self::$_user);
        $password = $account->get_password();

        midcom::get()->auth->request_sudo('org.openpsa.user');
        $this->assertTrue($accounthelper->disable_account());
        $account = new midcom_core_account(self::$_user);
        $this->assertEquals('', $account->get_password());
        $this->assertEquals($password, self::$_user->get_parameter('org_openpsa_user_blocked_account', 'account_password'));

        $args = [
            'guid' => self::$_user->guid,
            'parameter_name' => 'org_openpsa_user_blocked_account',
            'password' => 'account_password',
        ];

        $qb = midcom_services_at_entry_dba::new_query_builder();
        $qb->add_constraint('argumentsstore', '=', serialize($args));
        $result = $qb->execute();
        $this->register_objects($result);
        $this->assertCount(1, $result);

        $account->set_password($accounthelper->generate_safe_password());
        $account->save();
        midcom::get()->auth->drop_sudo();
    }

    /**
     * @depends testCheck_password_strength
     * @depends testCheck_password_reuse
     * @depends testGenerate_safe_password
     */
    public function testSet_account()
    {
        $user = self::create_user();
        $accounthelper = new org_openpsa_user_accounthelper($user);
        $account = new midcom_core_account($user);
        $password = $user->extra;
        $username = $account->get_username();
        midcom::get()->auth->request_sudo('org.openpsa.user');
        do {
            $new_password = $accounthelper->generate_safe_password();
        } while ($password === $new_password);

        $new_username = $username . time();

        $this->assertTrue($accounthelper->set_account($new_username, $new_password));
        midcom::get()->auth->drop_sudo();
        $account = new midcom_core_account($user);
        $this->assertTrue(midcom_connection::verify_password($new_password, $account->get_password()));
        $this->assertEquals($new_username, $account->get_username());
        $this->assertNotNull($user->get_parameter('org_openpsa_user_password', 'last_change'));
        $old = unserialize($user->get_parameter('org_openpsa_user_password', 'old_passwords'));
        $this->assertCount(1, $old);
        $this->assertTrue(midcom_connection::verify_password($password, $old[0]));
    }

    /**
     * 
     */
    public function testWelcome_email()
    {
        $user = self::create_user();
        $accounthelper = new org_openpsa_user_accounthelper($user);
        midcom::get()->auth->request_sudo('org.openpsa.user');
        $this->assertTrue($accounthelper->welcome_email());
        midcom::get()->auth->drop_sudo();
    }    
}
