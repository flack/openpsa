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
class midcom_helper_filesync_exporter_snippetTest extends openpsa_testcase
{
    protected static $_rootdir;

    public static function setUpBeforeClass()
    {
        self::$_rootdir = openpsa_test_fs_setup::get_exportdir('snippet');
    }

    public function test_read_root()
    {
        $snippetdir_name = uniqid('snippetdir_' . __CLASS__ . __FUNCTION__);
        $element_name = uniqid('element_' . __CLASS__ . __FUNCTION__);
        $snippetdir = $this->create_object('midcom_db_snippetdir', ['name' => $snippetdir_name]);
        $sub_snippetdir = $this->create_object('midcom_db_snippetdir', ['name' => $snippetdir_name, 'up' => $snippetdir->id]);
        $element = $this->create_object('midcom_db_snippet', ['name' => $element_name, 'snippetdir' => $sub_snippetdir->id]);

        $exporter = new midcom_helper_filesync_exporter_snippet(self::$_rootdir);
        midcom::get()->auth->request_sudo('midcom.helper.filesync');
        $stat = $exporter->read_root($snippetdir->id);
        midcom::get()->auth->drop_sudo();

        $this->assertTrue($stat);
        $this->assertFileExists(self::$_rootdir . $snippetdir_name);
        $this->assertFileExists(self::$_rootdir . $snippetdir_name . '/' . $snippetdir_name);
        $this->assertFileExists(self::$_rootdir . $snippetdir_name . '/' . $snippetdir_name . '/' . $element_name . '.php');
    }
}
