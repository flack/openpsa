<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\core;

use openpsa_testcase;
use midcom_db_topic;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class collectorTest extends openpsa_testcase
{
    protected static $_topic;

    public static function setUpBeforeClass() : void
    {
        self::$_topic = self::create_class_object(midcom_db_topic::class, [
            'name' => \midcom_helper_misc::urlize(__CLASS__) . time()
        ]);
    }

    /**
     * This is to test for infinite loops that happen in mgd core
     */
    public function text_duplicate_execution()
    {
        $mc = midcom_db_topic::new_collector('id', self::$_topic->id);
        $this->assertTrue($mc->execute());
        $this->assertTrue($mc->execute());

        $keys = $mc->list_keys();
        $this->assertEquals($keys, $mc->list_keys());
        $count = $mc->count();
        $this->assertEquals($count, $mc->count());

        $values = $mc->get_values('name');
        $this->assertEquals($values, $mc->get_values('name'));
    }

    public function test_list_keys()
    {
        $mc = midcom_db_topic::new_collector('id', self::$_topic->id);
        $keys = $mc->list_keys();

        $this->assertCount(1, $keys);
        $this->assertArrayHasKey(self::$_topic->guid, $keys);
        // This is to test for infinite loops that happen in mgd core
        $this->assertEquals($keys, $mc->list_keys());
    }

    public function test_count()
    {
        $mc = midcom_db_topic::new_collector('id', self::$_topic->id);

        $this->assertEquals(1, $mc->count());
    }

    public function test_get_values()
    {
        $mc = midcom_db_topic::new_collector('id', self::$_topic->id);
        $values = $mc->get_values('name');

        $this->assertCount(1, $values);
        $this->assertEquals($values, [self::$_topic->guid => self::$_topic->name]);
    }

    public function test_get_objects()
    {
        $mc = midcom_db_topic::new_collector('id', self::$_topic->id);
        $objects = $mc->get_objects();

        $this->assertCount(1, $objects);
        $this->assertEquals(self::$_topic->guid, $objects[0]->guid);
    }
}
