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
class privilegesTest extends openpsa_testcase
{
    protected static $_user;

    public static function setUpBeforeClass() : void
    {
        self::$_user = self::create_user(true);
    }

    public function test_handler_privileges()
    {
        midcom::get()->auth->request_sudo('org.openpsa.user');

        $data = $this->run_handler('org.openpsa.user', ['privileges', self::$_user->guid]);
        $this->assertEquals('user_privileges', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
