<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR);
    require_once OPENPSA_TEST_ROOT . 'rootfile.php';
}

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_db_attachmentTest extends openpsa_testcase
{
    protected static $_topic;
    protected static $_filepath;

    public static function setUpBeforeClass()
    {
        self::$_topic = self::create_class_object('midcom_db_topic');
        self::$_filepath = dirname(__FILE__) . '/__files/';
    }

    public function testCRUD()
    {
        midcom::get('auth')->request_sudo('midcom.core');

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

        midcom::get('auth')->drop_sudo();
    }

    public function test_copy_from_file()
    {
        $attachment = $this->create_object('midcom_db_attachment', array('parentguid' => self::$_topic->guid));
        $stat = $attachment->copy_from_file(self::$_filepath . 'attach.png');
        $this->assertTrue($stat, midcom_connection::get_error_string());

        $blob = new midgard_blob($attachment->__object);
        $this->assertFileEquals(self::$_filepath . 'attach.png', $blob->get_path());
    }

    public function test_get_cache_path()
    {
        $properties = array
        (
            'parentguid' => self::$_topic->guid,
            'name' => 'attach.png'
        );
        $attachment = $this->create_object('midcom_db_attachment', $properties);
        $attachment->copy_from_file(self::$_filepath . 'attach.png');

        $GLOBALS['midcom_config']['attachment_cache_enabled'] = false;
        $stat = midcom_db_attachment::get_cache_path($attachment);
        $this->assertNull($stat);

        $GLOBALS['midcom_config']['attachment_cache_enabled'] = true;
        $expected_path = $GLOBALS['midcom_config']['attachment_cache_root'] . '/' . substr($attachment->guid, 0,1) . '/' . $attachment->guid . '_attach.png';

        $stat = midcom_db_attachment::get_cache_path($attachment);
        $this->assertEquals($expected_path, $stat);
    }

    public function test_file_to_cache()
    {
        $properties = array
        (
            'parentguid' => self::$_topic->guid,
            'name' => 'attach.png'
        );
        $attachment = $this->create_object('midcom_db_attachment', $properties);
        $attachment->copy_from_file(self::$_filepath . 'attach.png');

        $GLOBALS['midcom_config']['attachment_cache_enabled'] = true;

        $expected_path = $GLOBALS['midcom_config']['attachment_cache_root'] . '/' . substr($attachment->guid, 0,1) . '/' . $attachment->guid . '_attach.png';

        $GLOBALS['midcom_config']['attachment_cache_enabled'] = true;
        $attachment->file_to_cache();
        $this->assertFileExists($expected_path);
    }

    /**
     * @dataProvider provider_safe_filename
     */
    public function test_safe_filename($input, $extension, $output)
    {
        $converted = midcom_db_attachment::safe_filename($input, $extension);
        $this->assertEquals($converted, $output);
    }

    public function provider_safe_filename()
    {
        return array
        (
            array('Minä olen huono tiedosto.foo.jpg', true, 'mina_olen_huono_tiedosto-foo.jpg'),
            array('Minä olen huono tiedosto.foo.jpg', false, 'mina_olen_huono_tiedosto.foo.jpg'),
            array('Minä olen huono tiedosto ilman päätettä', true, 'mina_olen_huono_tiedosto_ilman_paatetta'),
            array('Minä olen huono tiedosto ilman päätettä', false, 'mina_olen_huono_tiedosto_ilman_paatetta'),
        );
    }
}
?>