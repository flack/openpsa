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
class midcom_helper_filesync_exporter_styleTest extends openpsa_testcase
{
    protected static $_rootdir;

    public static function setUpBeforeClass()
    {
        self::$_rootdir = openpsa_test_fs_setup::get_exportdir('style');
    }

    public function test_read_root()
    {
        $style_name = uniqid('style_' . __CLASS__ . __FUNCTION__);

        $element_name = uniqid('element_' . __CLASS__ . __FUNCTION__);
        $style = $this->create_object(midcom_db_style::class, ['name' => $style_name]);
        $sub_style = $this->create_object(midcom_db_style::class, ['name' => $style_name, 'up' => $style->id]);
        $this->create_object(midcom_db_element::class, ['name' => $element_name, 'style' => $sub_style->id]);

        $exporter = new midcom_helper_filesync_exporter_style(self::$_rootdir);
        midcom::get()->auth->request_sudo('midcom.helper.filesync');
        $stat = $exporter->read_root($style->id);
        midcom::get()->auth->drop_sudo();

        $this->assertTrue($stat);
        $this->assertFileExists(self::$_rootdir . $style_name);
        $this->assertFileExists(self::$_rootdir . $style_name . '/' . $style_name);
        $this->assertFileExists(self::$_rootdir . $style_name . '/' . $style_name . '/' . $element_name . '.php');
    }
}
