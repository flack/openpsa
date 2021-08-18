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

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class editTest extends openpsa_testcase
{
    protected static $_user;

    public static function setUpBeforeClass() : void
    {
        self::$_user = self::create_user(true);
    }

    public function test_handler_edit()
    {
        midcom::get()->auth->request_sudo('org.openpsa.user');

        $data = $this->run_handler('org.openpsa.user', ['edit', self::$_user->guid]);
        $this->assertEquals('user_edit', $data['handler_id']);
        $this->assertEquals('person', $data['controller']->get_datamanager()->get_schema()->get('description'));

        $formdata = [
            'email' => 'test@test.info',
            'lastname' => 'TEST'
        ];

        $this->submit_dm_no_relocate_form('controller', $formdata, 'org.openpsa.user', ['edit', self::$_user->guid]);
        self::$_user->refresh();

        $this->assertEquals('test@test.info', self::$_user->email);

        midcom::get()->auth->drop_sudo();
    }
}
