<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\helper;

use openpsa_testcase;
use midcom_db_topic;
use midcom;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class metadataTest extends openpsa_testcase
{
    /**
     * @dataProvider providerEmptyProperties
     */
    public function testEmptyProperties($name)
    {
        $topic = new midcom_db_topic();
        $this->assertIsObject($topic->metadata);
        $this->assertEmpty($topic->metadata->$name);
    }

    public function providerEmptyProperties()
    {
        return [
            ['schedulestart'],
            ['scheduleend'],
            ['navnoentry'],
            ['hidden'],
            ['keywords'],
            ['description'],
            ['robots'],
            ['published'],
            ['publisher'],
            ['created'],
            ['revised'],
            ['approved'],
        ];
    }

    public function test_isset()
    {
        $topic = new midcom_db_topic();
        $this->assertTrue(isset($topic->metadata->created));
        $this->assertFalse(isset($topic->metadata->robots));
        $t2 = $this->create_object(midcom_db_topic::class);
        $this->assertTrue(isset($t2->metadata->created));
        $this->assertFalse(isset($t2->metadata->robots));
    }

    public function test_delete()
    {
        $topic = $this->create_object(midcom_db_topic::class);
        $this->assertFalse($topic->metadata->deleted);
        midcom::get()->auth->request_sudo('midcom.core');
        $this->assertTrue($topic->delete());
        midcom::get()->auth->drop_sudo();
        $this->assertTrue($topic->metadata->deleted);
    }

    /**
     * @depends testEmptyProperties
     * @dataProvider providerSetEmptyObjectProperties
     */
    public function testSetEmptyObjectProperties($field, $value)
    {
        $topic = new midcom_db_topic();
        $topic->metadata->$field = $value;
        $this->assertEquals($value, $topic->metadata->$field);
    }

    public function providerSetEmptyObjectProperties()
    {
        return [
            ['schedulestart', 1329255039],
            ['scheduleend', 0],
            ['navnoentry', true],
            ['hidden', false],
            ['published', time()],
        ];
    }

    /**
     * @depends testSetEmptyObjectProperties
     * @dataProvider providerSetDBObjectProperties
     */
    public function testSetDBObjectProperties($field, $expected)
    {
        $topic = $this->create_object(midcom_db_topic::class);
        midcom::get()->auth->request_sudo('midcom.core');
        $topic->metadata->$field = $expected;
        midcom::get()->auth->drop_sudo();
        $this->assertEquals($expected, $topic->metadata->$field);
    }

    public function providerSetDBObjectProperties()
    {
        return [
            ['schedulestart', 1329255039],
            ['scheduleend', 0],
            ['navnoentry', true],
            ['hidden', false],
            ['keywords', 'test kewords'],
            ['description', 'test description'],
            ['robots', 'test robots'],
            ['published', time()],
            ['publisher', 'cda8160834f7502bcdb71537c2772cc6'],
        ];
    }
}
