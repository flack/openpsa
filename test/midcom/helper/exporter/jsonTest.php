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
        $arr = array("message" => "hello world", "code" => 200, "object" => $this->_get_object());

        $mapper = new midcom_helper_exporter_json();
        $data = $mapper->array2data($arr);

        $expected = json_encode(array("message" => $arr["message"], "code" => $arr["code"], "object" => $this->_get_data_array()));
        $this->assertEquals($expected, $data);
    }

    public function test_data2array()
    {
        $data = $this->_get_data();

        $mapper = new midcom_helper_exporter_json();
        $arr = $mapper->data2array($data);

        $expected = $this->_get_data_array();
        $this->assertEquals($expected, $arr);
    }

    public function test_object2data()
    {
        $object = $this->_get_object();

        $mapper = new midcom_helper_exporter_json();
        $data = $mapper->object2data($object);

        $expected = $this->_get_data();
        $this->assertEquals($expected, $data);
    }

    private function _get_object()
    {
        $object = new midcom_db_element;
        $object->value = "test";
        $object->style = 33;
        $object->name = 'Test';
        return $object;
    }

    private function _get_data()
    {
        //different results according to different extention versions
        if (extension_loaded('midgard'))
        {
            return '{"metadata":{"guid":"","created":0,"hidden":false,"deleted":false,"isapproved":false,"islocked":false},"guid":"","sitegroup":1,"action":"","id":0,"style":33,"sid":0,"lang":0,"value":"test","name":"Test"}';
        }
        return '{"guid":"","metadata":{"guid":"","created":0,"hidden":false,"deleted":false,"isapproved":false,"islocked":false},"action":"","id":0,"name":"Test","style":33,"value":"test"}';
    }

    private function _get_data_array()
    {
        //different results according to different extention versions
        if (extension_loaded('midgard'))
        {
            return array
            (
                'metadata' => array
                (
                        'guid' => '',
                        'created' => 0,
                        'hidden' => false,
                        'deleted' => false,
                        'isapproved' => false,
                        'islocked' => false
                ),
                'guid' => '',
                'sitegroup' => 1,
                'action' => '',
                'id' => 0,
                'style' => 33,
                'sid' => 0,
                'lang' => 0,
                'value' => 'test',
                'name' => 'Test',
            );
            
        }
        
        return array
        (
            'guid' => '',
            'metadata' => array
            (
                'guid' => '',
                'created' => 0,
                'hidden' => false,
                'deleted' => false,
                'isapproved' => false,
                'islocked' => false
            ),
            'action' => '',
            'id' => 0,
            'name' => 'Test',
            'style' => 33,
            'value' => 'test'
        );
    }
}
?>