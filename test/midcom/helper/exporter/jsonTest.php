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
class midcom_helper_exporter_jsonTest extends openpsa_testcase
{
    public function test_array2data()
    {
        $arr = [
            "message" => "hello world",
            "code" => 200,
            "object" => $this->_get_object()
        ];

        $mapper = new midcom_helper_exporter_json();
        $data = $mapper->array2data($arr);

        $expected = json_encode([
            "message" => $arr["message"],
            "code" => $arr["code"],
            "object" => $this->_get_data_array($arr['object']->metadata->creator)]
        );
        $this->assertJsonStringEqualsJsonString($expected, $data);
    }

    public function test_data2array()
    {
        $expected = $this->_get_data_array('');

        $mapper = new midcom_helper_exporter_json();
        $arr = $mapper->data2array(json_encode($expected));

        // assert arrays are equal
        $this->assertEquals($expected, $arr);
    }

    public function test_object2data()
    {
        $object = $this->_get_object();

        $mapper = new midcom_helper_exporter_json();
        $data = $mapper->object2data($object);

        $expected = json_encode($this->_get_data_array($object->metadata->creator));
        $this->assertJsonStringEqualsJsonString($expected, $data);
    }

    private function _get_object()
    {
        $object = new midcom_db_element;
        $object->value = "test";
        $object->style = 33;
        $object->name = 'Test';
        return $object;
    }

    private function _get_data_array($creator)
    {
        $data = [
            'guid' => '',
            'id' => 0,
            'name' => 'Test',
            'style' => 33,
            'value' => 'test',
            'metadata' => [
                'creator' => $creator,
                'created' => 0,
                'revisor' => $creator,
                'revised' => 0,
                'revision' => 0,
                'locker' => $creator,
                'locked' => 0,
                'approver' => $creator,
                'approved' => 0,
                'authors' => '',
                'owner' => '',
                'schedulestart' => 0,
                'scheduleend' => 0,
                'navnoentry' => false,
                'size' => 0,
                'published' => 0,
                'score' => 0,
                'imported' => 0,
                'hidden' => false,
                'deleted' => false,
                'isapproved' => false,
                'islocked' => false,
                'exported' => 0
           ]
        ];
        return $data;
    }
}
