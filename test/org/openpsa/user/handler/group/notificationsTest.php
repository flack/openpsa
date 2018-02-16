<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class org_openpsa_user_handler_group_notificationsTest extends openpsa_testcase
{
    protected static $_user;

    public static function setUpBeforeClass()
    {
        self::$_user = self::create_user(true);
    }

    public function test_handler_notifications()
    {
        midcom::get()->auth->request_sudo('org.openpsa.user');

        $group = $this->create_object(midcom_db_group::class);

        $data = $this->run_handler('org.openpsa.user', ['group', 'notifications', $group->guid]);
        $this->assertEquals('group_notifications', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
