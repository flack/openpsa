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
class midgard_admin_user_handler_group_listTest extends openpsa_testcase
{
    protected static $_group;

    public static function setupBeforeClass()
    {
        self::$_group = self::create_class_object('midcom_db_group');
    }

    public function testHandler_list()
    {
        midcom::get()->auth->request_sudo('midgard.admin.user');

        $data = $this->run_handler('net.nehmer.static', array('__mfa', 'asgard_midgard.admin.user', 'group'));
        $this->assertEquals('____mfa-asgard_midgard.admin.user-group_list', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_move()
    {
        midcom::get()->auth->request_sudo('midgard.admin.user');

        $data = $this->run_handler('net.nehmer.static', array('__mfa', 'asgard_midgard.admin.user', 'group', 'move', self::$_group->guid));
        $this->assertEquals('____mfa-asgard_midgard.admin.user-group_move', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function test_belongs_to()
    {
        $root = $this->create_object('midcom_db_group');
        $child = $this->create_object('midcom_db_group', array('owner' => $root->id));
        $grandchild = $this->create_object('midcom_db_group', array('owner' => $child->id));

        $other = $this->create_object('midcom_db_group');

        $this->assertTrue(midgard_admin_user_handler_group_list::belongs_to($root->id, $root->id));
        $this->assertTrue(midgard_admin_user_handler_group_list::belongs_to($child->id, $root->id));
        $this->assertTrue(midgard_admin_user_handler_group_list::belongs_to($grandchild->id, $root->id));
        $this->assertFalse(midgard_admin_user_handler_group_list::belongs_to($root->id, $other->id));
        $this->assertFalse(midgard_admin_user_handler_group_list::belongs_to($grandchild->id, $other->id));
    }
}
