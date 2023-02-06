<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\user\handler\group;

use midcom_db_person;
use openpsa_testcase;
use midcom;
use midcom_db_group;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class viewTest extends openpsa_testcase
{
    protected static midcom_db_person $_user;

    public static function setUpBeforeClass() : void
    {
        self::$_user = self::create_user(true);
    }

    public function test_handler_view()
    {
        midcom::get()->auth->request_sudo('org.openpsa.user');

        $group = $this->create_object(midcom_db_group::class);

        $data = $this->run_handler('org.openpsa.user', ['group', $group->guid]);
        $this->assertEquals('group_view', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
