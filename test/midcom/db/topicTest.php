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
class midcom_db_topicTest extends openpsa_testcase
{
    protected static $_parent;

    public static function setUpBeforeClass()
    {
        self::$_parent = self::create_class_object(midcom_db_topic::class);
    }

    public function testCRUD()
    {
        midcom::get()->auth->request_sudo('midcom.core');

        $topic = new midcom_db_topic();
        $topic->topic = self::$_parent->id;
        $topic->_use_rcs = false;
        $stat = $topic->create();
        $this->assertTrue($stat, midcom_connection::get_error_string());

        $this->register_object($topic);

        $topic->title = 'test';
        $stat = $topic->update();
        $this->assertTrue($stat);
        $this->assertEquals('test', $topic->title);

        $stat = $topic->delete();
        $this->assertTrue($stat);

        midcom::get()->auth->drop_sudo();
    }

    public function test_get_parent()
    {
        $topic1 = new midcom_db_topic;
        $topic1->up = self::$_parent->id;
        $parent1 = $topic1->get_parent();
        $this->assertEquals($parent1->guid, self::$_parent->guid);
        $this->assertEquals(get_class($parent1), get_class(self::$_parent));
    }
}
