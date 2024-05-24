<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\db;

use openpsa_testcase;
use midcom_db_topic;
use midcom;
use midcom_db_attachment;
use midcom_connection;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class attachmentTest extends openpsa_testcase
{
    protected static midcom_db_topic $_topic;
    protected static string $_filepath;

    public static function setUpBeforeClass() : void
    {
        self::$_topic = self::create_class_object(midcom_db_topic::class);
        self::$_filepath = __DIR__ . '/__files/';
    }

    public function testCRUD()
    {
        midcom::get()->auth->request_sudo('midcom.core');

        $attachment = new midcom_db_attachment();
        $stat = $attachment->create();
        $this->assertFalse($stat, midcom_connection::get_error_string());

        $attachment = new midcom_db_attachment();
        $attachment->parentguid = self::$_topic->guid;
        $stat = $attachment->create();
        $this->assertTrue($stat, midcom_connection::get_error_string());

        $this->register_object($attachment);

        $attachment->refresh();
        $this->assertEquals('application/octet-stream', $attachment->mimetype);
        $this->assertFalse(empty($attachment->location));
        $attachment->name = 'test.jpg';
        $stat = $attachment->update();
        $this->assertTrue($stat);
        $this->assertEquals('test.jpg', $attachment->name);

        $stat = $attachment->delete();
        $this->assertTrue($stat);

        midcom::get()->auth->drop_sudo();
    }

    private function _get_attachment(array $attributes)
    {
        $attachment = $this->create_object(midcom_db_attachment::class, $attributes);

        midcom::get()->auth->request_sudo('midcom.core');
        $this->assertTrue($attachment->copy_from_file(self::$_filepath . 'attach.png'));
        midcom::get()->auth->drop_sudo();
        return $attachment;
    }

    public function test_copy_from_file()
    {
        $attachment = $this->create_object(midcom_db_attachment::class, ['parentguid' => self::$_topic->guid]);

        midcom::get()->auth->request_sudo('midcom.core');
        $stat = $attachment->copy_from_file(self::$_filepath . 'attach.png');
        midcom::get()->auth->drop_sudo();
        $this->assertTrue($stat, midcom_connection::get_error_string());

        $this->assertFileEquals(self::$_filepath . 'attach.png', $attachment->get_path());
    }

    public function test_file_to_cache()
    {
        $properties = [
            'parentguid' => self::$_topic->guid,
            'name' => 'attach.png'
        ];
        $attachment = $this->_get_attachment($properties);

        midcom::get()->config->set('attachment_cache_enabled', true);

        $expected_path = midcom::get()->config->get('attachment_cache_root') . '/' . $attachment->guid[0] . '/' . $attachment->guid . '/attach.png';

        $attachment->file_to_cache();
        midcom::get()->config->set('attachment_cache_enabled', false);
        $this->assertFileExists($expected_path);
    }

    /**
     * @dataProvider provider_safe_filename
     */
    public function test_safe_filename($input, $output)
    {
        $converted = midcom_db_attachment::safe_filename($input);
        $this->assertEquals($converted, $output);
    }

    public static function provider_safe_filename()
    {
        return [
            ['Minä olen huono tiedosto.foo.jpg', 'minae-olen-huono-tiedosto-foo.jpg'],
            ['Minä olen huono tiedosto ilman päätettä', 'minae-olen-huono-tiedosto-ilman-paeaetettae'],
            ['www.openpsa2.org - Home.htm', 'www-openpsa2-org-home.htm'],
        ];
    }
}
