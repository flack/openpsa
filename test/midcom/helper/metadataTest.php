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
class midcom_helper_metadataTest extends openpsa_testcase
{
    /**
     * @dataProvider providerEmptyProperties
     */
    public function testEmptyProperties($data)
    {
        $name = $data[0];
        $topic = new midcom_db_topic();
        $this->assertTrue(is_object($topic->metadata));
        $this->assertTrue(isset($topic->metadata->$name));
        $this->assertTrue(empty($topic->metadata->$name));
    }

    public function providerEmptyProperties()
    {
        return [
            ['schedulestart'],
            ['scheduleend'],
            ['navnoentry'],
            ['hide'],
            ['keywords'],
            ['description'],
            ['robots'],
            ['published'],
            ['publisher'],
            ['created'],
            ['creator'],
            ['revised'],
            ['revisor'],
            ['approved'],
            ['approver'],
        ];
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
            ['hide', false],
            ['published', time()],
        ];
    }

    /**
     * @depends testSetEmptyObjectProperties
     * @dataProvider providerSetDBObjectProperties
     */
    public function testSetDBObjectProperties($field, $value, $expected)
    {
        $topic = $this->create_object(midcom_db_topic::class);
        midcom::get()->auth->request_sudo('midcom.core');
        $topic->metadata->$field = $value;
        midcom::get()->auth->drop_sudo();
        $this->assertEquals($topic->metadata->$field, $value);
    }


    public function providerSetDBObjectProperties()
    {
        return [
            ['schedulestart', 1329255039],
            ['scheduleend', 0],
            ['navnoentry', true],
            //array('hide', false),
            ['keywords', 'test kewords'],
            ['description', 'test description'],
            ['robots', 'test robots'],
            ['published', time()],
            //array('publisher', $person->guid),
        ];
    }
}
