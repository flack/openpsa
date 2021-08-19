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
use midcom_core_account;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class createTest extends openpsa_testcase
{
    protected static $_user;

    public static function setUpBeforeClass() : void
    {
        self::$_user = self::create_user(true);
    }

    public function test_handler_create()
    {
        midcom::get()->auth->request_sudo('org.openpsa.user');

        $data = $this->run_handler('org.openpsa.user', ['create']);
        $this->assertEquals('user_create', $data['handler_id']);

        $username = uniqid(__FUNCTION__);
        $formdata = [
            'firstname' => __CLASS__ . '::' . __FUNCTION__,
            'lastname' => __CLASS__ . '::' . __FUNCTION__,
            'email' => __FUNCTION__ . '@openpsa2.org',
            'username' => $username,
            'password' => [
                'switch' => '1',
                'password' => 'p@ssword123'
            ],
            'send_welcome_mail' => '1'
        ];

        $this->submit_dm_no_relocate_form('controller', $formdata, 'org.openpsa.user', ['create']);
        $url = $this->get_dialog_url();

        $tokens = explode('/', trim($url, '/'));

        $guid = end($tokens);
        $person = new midcom_db_person($guid);
        $this->register_object($person);

        $this->assertEquals(__CLASS__ . '::' . __FUNCTION__, $person->firstname);
        $this->assertEquals(__CLASS__ . '::' . __FUNCTION__, $person->lastname);

        $account = new midcom_core_account($person);
        $this->assertEquals($username, $account->get_username());

        midcom::get()->auth->drop_sudo();
    }
}
