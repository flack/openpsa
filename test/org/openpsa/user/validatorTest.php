<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\user;

use midcom_db_person;
use openpsa_testcase;
use midcom_core_account;
use midcom;
use org_openpsa_user_validator;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class validatorTest extends openpsa_testcase
{
    protected static midcom_db_person $_user;

    public static function setUpBeforeClass() : void
    {
        self::$_user = self::create_user();
    }

    public function testValidate_edit_form()
    {
        $val = new org_openpsa_user_validator;

        $person = self::create_user(true);
        $account = new midcom_core_account($person);

        // this should work
        $fields = [
            "person" => $person->guid,
            "username" => $account->get_username(),
            "current_password" => $person->extra
        ];
        $this->assertTrue($val->validate_edit_form($fields));

        // try changing the username
        $fields = [
            "person" => $person->guid,
            "username" => $account->get_username(),
            "current_password" => "abc"
        ];
        $result = $val->validate_edit_form($fields);
        $this->assertArrayHasKey("current_password", $result);

        // now, use sudo..
        midcom::get()->auth->request_sudo("midcom.core");
        // try setting another password
        $fields = [
            "person" => $person->guid,
            "username" => $account->get_username(),
            "current_password" => "abc"
        ];
        $this->assertTrue($val->validate_edit_form($fields));

        // try using another username
        $fields = [
            "person" => $person->guid,
            "username" => uniqid(__FUNCTION__ . "Bob"),
            "current_password" => $account->get_password()
        ];
        $this->assertTrue($val->validate_edit_form($fields));

        // try changing the password - this should not work - too weak
        $fields = [
            "person" => $person->guid,
            "username" => $account->get_username(),
            "current_password" => $person->extra,
            "new_password" => 'as'
        ];
        $result = $val->validate_edit_form($fields);
        $this->assertIsArray($result);

        midcom::get()->auth->drop_sudo();
    }

    public function testUsername_exists()
    {
        $val = new org_openpsa_user_validator;

        $person = self::create_user();
        $account = new midcom_core_account($person);

        // try valid username
        $this->assertTrue($val->username_exists(["username" => $account->get_username()]));

        // try invalid username
        $result = $val->username_exists(["username" => uniqid(__FUNCTION__ . "FAKE_BOB")]);
        $this->assertArrayHasKey("username", $result);
    }

    public function testEmail_exists()
    {
        $val = new org_openpsa_user_validator;

        $person = self::create_user();

        // try invalid email
        $result = $val->email_exists(["email" => uniqid(__FUNCTION__ . "-fake-mail-") . "@nowhere.cc"]);
        $this->assertArrayHasKey("email", $result);

        // try valid email
        $email = uniqid(__FUNCTION__ . "-user-") . "@nowhere.cc";
        $person->email = $email;
        $person->update();

        $this->assertTrue($val->email_exists(["email" => $email]));
    }

    public function testEmail_and_username_exist()
    {
        $val = new org_openpsa_user_validator;

        $person = self::create_user();
        $account = new midcom_core_account($person);

        // try invalid combination
        $fields = [
            "username" => $account->get_username(),
            "email" => uniqid(__FUNCTION__ . "-fake-mail-") . "@nowhere.cc"
        ];

        $result = $val->email_and_username_exist($fields);
        $this->assertArrayHasKey("username", $result);

        // use invalid username as well
        $fields["username"] = uniqid(__FUNCTION__ . "-fake-user-");
        $this->assertArrayHasKey("username", $result);

        // try valid combination
        $email = uniqid(__FUNCTION__ . "-user-") . "@nowhere.cc";
        $person->email = $email;
        $person->update();

        $fields = [
            "username" => $account->get_username(),
            "email" => $email
        ];

        $this->assertTrue($val->email_and_username_exist($fields));
    }
}
