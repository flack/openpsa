<?php
/**
 * @author tarjei huse
 * @package midcom.helper.xml
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 *
 */

$_MIDCOM->load_library('midcom.helper.xml');
$GLOBALS['testclasses'] = array ('midcom_helper_xml_objectmapper_test' => 0);

/**
 * This class tests the midcom_helper_xml_objectmapper
 * @package midcom.helper.xml
 */
class midcom_helper_xml_objectmapper_test extends UnitTestCase
{
    /**
     * Testdata
     */
    var $testdata_1 = "<topic>
                            <realm>topic</realm>
                            <guid>0942711831d4fa72e327a6f00fde0405</guid>
                            <action>update</action>
                            <errno>0</errno>
                            <errstr />
                            <id>4</id>
                            <name>__Midgard CMS Welcome topic</name>
                            <extra />
                            <score>0</score>
                            <description />
                            <code />
                            <revised>1136586581</revised>
                            <revision>2</revision>
                            <created>1095430029</created>
                            <owner>3</owner>
                            <revisor>1</revisor>
                            <creator>1</creator>
                            <up>0</up>
                            </topic>";
    /**
     * objectmapper instance
     */
    var $objectmapper = null;

    function setUp()
    {
        $this->objectmapper = new midcom_helper_xml_objectmapper();
    }

    /**
     * Test
     */
    function test_objectmapper_no_data()
    {
        $this->assertFalse($this->objectmapper->object2data(null), "Parse should return false on no data.");
        $this->assertTrue($this->objectmapper->errstr != '', "The errorstring should be set.");
    }

    function test_objectmapper_object2data()
    {
        $object = $this->make_test_object();
        $data = $this->objectmapper->object2data($object);
        $this->assertTrue(is_string($data) && $data != '', "Object to data should return a string here. It returned: ".gettype($data));

        $toarray = new midcom_helper_xml_toarray();
        $array = $toarray->parse($data);

        $this->assertTrue(is_array($array), "The data must be parseable back to an array");
        $class = $this->objectmapper->_get_classname($object);
        $this->assertTrue(array_key_exists($class, $array), "The array should have the objectclass as it's first key");

        $this->assertTrue(in_array($class, midcom_connection::get_schema_types()), "The array should have the objectclass as its first key: $class");
    }

    function test_objectmapper_data2object()
    {
        $object = $this->make_test_object();
        $object2 = $this->objectmapper->data2object($this->testdata_1, $object);
        $this->assertTrue(is_object($object2), "data2object should return an object when inputs are valid: ".$this->objectmapper->errstr);
        $this->assertTrue($object2->action == 'update', "Action should be set by the mapper. Current value:".$object2->action);
        $this->assertTrue($object2->id == '', "Id isn't set on the testobject and shouldn't be changed by the mapper. Current value:".$object2->id);
        $this->assertTrue($object2->description == '', "Values that are empty should be set accordingly");
        $this->assertTrue($object2->owner == 3);

        var_dump($object2);
    }

    function make_test_object()
    {
        $object = new midcom_db_topic();
        $object->name = "Test topic";
        $object->description = "labi la";
        $object->code = "Cody code";
        return $object;
    }
}
