<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midgard\admin\asgard\handler\object;

use openpsa_testcase;
use midcom_db_topic;
use midcom;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class manageTest extends openpsa_testcase
{
    protected static $_object;

    public static function setUpBeforeClass() : void
    {
        self::$_object = self::create_class_object(midcom_db_topic::class);
    }

    public function testHandler_view()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('midgard.admin.asgard');

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard', 'object', 'view', self::$_object->guid]);
        $this->assertEquals('object_view', $data['handler_id']);
        $output = $data['__openpsa_testcase_response']->getContent();
        $this->assertStringContainsString(' class="midcom_helper_datamanager2_view"', $output);
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_edit()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('midgard.admin.asgard');

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard', 'object', 'edit', self::$_object->guid]);
        $this->assertEquals('object_edit', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_copy()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('midgard.admin.asgard');

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard', 'object', 'copy', self::$_object->guid]);
        $this->assertEquals('object_copy', $data['handler_id']);

        $formdata = [];

        $this->submit_dm_form('controller', $formdata, 'net.nehmer.static', ['__mfa', 'asgard', 'object', 'copy', self::$_object->guid]);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_copy_tree()
    {
        $this->create_object(midcom_db_topic::class, ['up' => self::$_object->id]);
        $this->create_user(true);
        midcom::get()->auth->request_sudo('midgard.admin.asgard');

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard', 'object', 'copy', 'tree', self::$_object->guid]);
        $this->assertEquals('object_copy_tree', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_create_chooser()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('midgard.admin.asgard');

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard', 'object', 'create', 'chooser', 'midgard_article']);
        $this->assertEquals('object_create_chooser', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_create()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('midgard.admin.asgard');

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard', 'object', 'create', 'midgard_article', self::$_object->guid]);
        $this->assertEquals('object_create', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_create_toplevel()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('midgard.admin.asgard');

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard', 'object', 'create', 'midgard_topic']);
        $this->assertEquals('object_create_toplevel', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_delete()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('midgard.admin.asgard');

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard', 'object', 'delete', self::$_object->guid]);
        $this->assertEquals('object_delete', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
