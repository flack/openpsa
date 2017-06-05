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
class midgard_admin_asgard_handler_object_rcsTest extends openpsa_testcase
{
    protected static $_object;

    public static function setUpBeforeClass()
    {
        self::create_user(true);
        self::$_object = self::create_class_object('midcom_db_person', ['_use_rcs' => true]);
        self::$_object->update();
        self::$_object->lastname = 'test';
        self::$_object->update();
    }

    public function testHandler_history()
    {
        $object_without_history = self::create_class_object('midcom_db_topic', ['_use_rcs' => false]);

        midcom::get()->auth->request_sudo('midgard.admin.asgard');

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard', 'object', 'rcs', self::$_object->guid]);
        $this->assertEquals('____mfa-asgard-object_rcs_history', $data['handler_id']);

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard', 'object', 'rcs', $object_without_history->guid]);
        $this->show_handler($data);
        $this->assertEquals('____mfa-asgard-object_rcs_history', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_preview()
    {
        midcom::get()->auth->request_sudo('midgard.admin.asgard');

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard', 'object', 'rcs', 'preview', self::$_object->guid, '1.1']);
        $this->assertEquals('____mfa-asgard-object_rcs_preview', $data['handler_id']);
        $this->show_handler($data);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_diff()
    {
        midcom::get()->auth->request_sudo('midgard.admin.asgard');

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard', 'object', 'rcs', 'diff', self::$_object->guid, '1.1', '1.2']);
        $this->assertEquals('____mfa-asgard-object_rcs_diff', $data['handler_id']);
        $this->show_handler($data);

        midcom::get()->auth->drop_sudo();
    }
}
