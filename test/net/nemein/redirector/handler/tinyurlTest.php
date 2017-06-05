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
class net_nemein_redirector_handler_tinyurlTest extends openpsa_testcase
{
    protected static $_topic;

    public static function setUpBeforeClass()
    {
        $topic_attributes = [
            'component' => 'net.nemein.redirector',
            'name' => __CLASS__ . time()
        ];
        self::$_topic = self::create_class_object('midcom_db_topic', $topic_attributes);
    }

    public function testHandler_create()
    {
        midcom::get()->auth->request_sudo('net.nemein.redirector');

        $data = $this->run_handler(self::$_topic, ['create']);
        $this->assertEquals('create', $data['handler_id']);

        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_edit()
    {
        midcom::get()->auth->request_sudo('net.nemein.redirector');
        $attributes = [
            'node' => self::$_topic->guid,
            'name' => net_nemein_redirector_tinyurl_dba::generate()
        ];
        $tinyurl = $this->create_object('net_nemein_redirector_tinyurl_dba', $attributes);

        $data = $this->run_handler(self::$_topic, ['edit', $tinyurl->name]);
        $this->assertEquals('edit', $data['handler_id']);

        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }
}
