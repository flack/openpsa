<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

require_once OPENPSA_TEST_ROOT . 'midcom/helper/filesync/__files/fs_setup.php';

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_helper_filesync_exporter_structureTest extends openpsa_testcase
{
    protected static $_rootdir;

    public static function setUpBeforeClass()
    {
        self::$_rootdir = openpsa_test_fs_setup::get_exportdir('structure');
    }

    public function test_read_structure()
    {
        $topic_name = uniqid('structure_' . __CLASS__ . __FUNCTION__);
        $export_name = 'export';

        $topic = $this->create_object('midcom_db_topic', array('name' => $topic_name));
        $sub_topic = $this->create_object('midcom_db_topic', array('name' => $topic_name, 'up' => $topic->id));

        $exporter = new midcom_helper_filesync_exporter_structure(self::$_rootdir);
        midcom::get()->auth->request_sudo('midcom.helper.filesync');
        $exporter->read_structure($topic, $export_name);
        midcom::get()->auth->drop_sudo();

        $this->assertFileExists(self::$_rootdir . $export_name . '.inc');
        $structure = file_get_contents(self::$_rootdir . $export_name . '.inc');
        $structure_array = eval('return array(' . $structure . ');');

        $expected = array(
            'export' => array(
                'name' => 'export',
                'title' => 'localhost',
                'root' => array(
                    'name' => $topic_name,
                    'title' => '',
                    'component' => '',
                    'style' => '',
                    'style_inherit' => false,
                    'parameters' => array(),
                    'acl' => array(),
                    'nodes' => array(
                        $topic_name => array(
                            'name' => $topic_name,
                            'title' => '',
                            'component' => 'midcom.core.nullcomponent',
                            'style' => '',
                            'style_inherit' => false,
                            'parameters' => array(),
                            'acl' => array(),
                            'nodes' => array(),
                        ),
                    ),
                ),
            ),
        );

        $this->assertEquals($expected, $structure_array);
    }
}
