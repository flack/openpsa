<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midgard\admin\user\handler\group;

use openpsa_testcase;
use midcom;
use midcom_db_group;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class permissionsTest extends openpsa_testcase
{
    protected static midcom_db_group $_group;

    public static function setUpBeforeClass() : void
    {
        self::$_group = self::create_class_object(midcom_db_group::class);
    }

    public function testHandler_folders()
    {
        midcom::get()->auth->request_sudo('midgard.admin.user');

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard_midgard.admin.user', 'group', 'folders', self::$_group->guid]);
        $this->assertEquals('group_folders', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
