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
        return array(
            array('schedulestart'),
            array('scheduleend'),
            array('navnoentry'),
            array('hide'),
            array('keywords'),
            array('description'),
            array('robots'),
            array('published'),
            array('publisher'),
            array('created'),
            array('creator'),
            array('revised'),
            array('revisor'),
            array('approved'),
            array('approver'),
        );
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
        return array(
            array('schedulestart', 1329255039),
            array('scheduleend', 0),
            array('navnoentry', true),
            array('hide', false),
            array('published', time()),
        );
    }

    /**
     * @depends testSetEmptyObjectProperties
     * @dataProvider providerSetDBObjectProperties
     */
    public function testSetDBObjectProperties($field, $value, $expected)
    {
        $topic = $this->create_object('midcom_db_topic');
        midcom::get()->auth->request_sudo('midcom.core');
        $topic->metadata->$field = $value;
        midcom::get()->auth->drop_sudo();
        $this->assertEquals($topic->metadata->$field, $value);
    }


    public function providerSetDBObjectProperties()
    {
        return array(
            array('schedulestart', 1329255039),
            array('scheduleend', 0),
            array('navnoentry', true),
            //array('hide', false),
            array('keywords', 'test kewords'),
            array('description', 'test description'),
            array('robots', 'test robots'),
            array('published', time()),
            //array('publisher', $person->guid),
        );
    }
}
