<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR);
    require_once OPENPSA_TEST_ROOT . 'rootfile.php';
}
require_once OPENPSA_TEST_ROOT . 'midcom/helper/filesync/__files/fs_setup.php';

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_helper_filesync_exporter_pageTest extends openpsa_testcase
{
    protected static $_rootdir;

    public static function setUpBeforeClass()
    {
        self::$_rootdir = openpsa_test_fs_setup::get_exportdir('page');
    }

    public function test_read_root()
    {
        $page_name = 'page_' . __CLASS__ . __FUNCTION__ . microtime(true);

        $element_name = 'element_' . __CLASS__ . __FUNCTION__ . microtime(true);
        $page = $this->create_object('midcom_db_page', array('name' => $page_name));
        $sub_page = $this->create_object('midcom_db_page', array('name' => $page_name, 'up' => $page->id));
        $element = $this->create_object('midcom_db_pageelement', array('name' => $element_name, 'page' => $sub_page->id));

        $exporter = new midcom_helper_filesync_exporter_page(self::$_rootdir);
        midcom::get('auth')->request_sudo('midcom.helper.filesync');
        $stat = $exporter->read_root($page->id);
        midcom::get('auth')->drop_sudo();

        $this->assertTrue($stat);
        $this->assertFileExists(self::$_rootdir . $page_name);
        $this->assertFileExists(self::$_rootdir . $page_name . '/' . $page_name);
        $this->assertFileExists(self::$_rootdir . $page_name . '/' . $page_name . '/' . $element_name . '.php');
    }
}
?>