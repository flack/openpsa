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
class midgard_admin_asgard_handler_object_attachmentsTest extends openpsa_testcase
{
    protected static $_object;
    protected static $_attachment;

    protected static $_filename = 'test.txt';

    public static function setUpBeforeClass()
    {
        self::$_object = self::create_class_object(midcom_db_topic::class);
        $parameters = [
            'parentguid' => self::$_object->guid,
            'name' => self::$_filename
        ];

        self::$_attachment = self::create_class_object(midcom_db_attachment::class, $parameters);
        midcom::get()->auth->request_sudo('midgard.admin.asgard');
        self::$_attachment->copy_from_file(dirname(dirname(dirname(__FILE__))) . '/__files/' . self::$_filename);
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_create()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('midgard.admin.asgard');

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard', 'object', 'attachments', self::$_object->guid]);
        $this->assertEquals('object_attachments', $data['handler_id']);
        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_edit()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('midgard.admin.asgard');

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard', 'object', 'attachments', self::$_object->guid, self::$_filename]);
        $this->assertEquals('object_attachments_edit', $data['handler_id']);
        $this->show_handler($data);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_delete()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('midgard.admin.asgard');

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard', 'object', 'attachments', 'delete', self::$_object->guid, self::$_filename]);
        $this->assertEquals('object_attachments_delete', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
