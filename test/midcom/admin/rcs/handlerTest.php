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
class midcom_admin_rcs_handlerTest extends openpsa_testcase
{
    protected static $_object;

    public static function setUpBeforeClass()
    {
        self::create_user(true);
        self::$_object = self::create_class_object(midcom_db_person::class, ['_use_rcs' => true]);
        self::$_object->update();
        self::$_object->lastname = 'test';
        self::$_object->update();
    }

    public function testHandler_history()
    {
        $object_without_history = self::create_class_object(midcom_db_topic::class, ['_use_rcs' => false]);

        midcom::get()->auth->request_sudo('midcom.admin.rcs');

        $data = $this->run_handler('net.nehmer.static', ['__ais', 'rcs', self::$_object->guid]);
        $this->assertEquals('history', $data['handler_id']);

        $data = $this->run_handler('net.nehmer.static', ['__ais', 'rcs', $object_without_history->guid]);
        $this->assertEquals('history', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_preview()
    {
        midcom::get()->auth->request_sudo('midcom.admin.rcs');

        $first = midcom::get()->rcs->load_backend(self::$_object)->get_history()->get_first()['revision'];
        $data = $this->run_handler('net.nehmer.static', ['__ais', 'rcs', 'preview', self::$_object->guid, $first]);
        $this->assertEquals('preview', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_diff()
    {
        midcom::get()->auth->request_sudo('midcom.admin.rcs');

        $history = midcom::get()->rcs->load_backend(self::$_object)->get_history();
        $first = $history->get_first()['revision'];
        $last = $history->get_last()['revision'];
        $data = $this->run_handler('net.nehmer.static', ['__ais', 'rcs', 'diff', self::$_object->guid, $first, $last]);
        $this->assertEquals('diff', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
