<?php
/**
 * Created on Jan 13, 2006
 * @author tarjei huse
 * @package midcom.helper.xml
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * 
 */
/**
 * This class tests the midcom_helper_xml_toarray class.
 * Run it through the midcom.tests component.
 */ 

$_MIDCOM->load_library('midcom.helper.xml');
$GLOBALS['testclasses'] = array ('midcom_helper_xml_toarray_test' => 0);
 

class midcom_helper_xml_toarray_test extends UnitTestCase {

    var $testdata_1 =  "<topic>
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
     * toarray instance 
     */
    var $toarray = null;
    
    function setUp() {
        $this->toarray = new midcom_helper_xml_toarray();
    }

    /**
     * Test
     */
    function test_toarray_no_data () 
    {
        
        $this->assertFalse($this->toarray->parse(''), "Parse should return false on no data.");
        
        $this->assertTrue($this->toarray->errstr != '', "The errorstring should be set.");
    }
    
    function test_toarray_false_data() {
        $data = 'weoirjoij/s</oij>';
        $this->assertFalse($this->toarray->parse($data), "Parse should return false on no data.");
        $this->assertTrue($this->toarray->errstr != '', "The errorstring should be set.");
    }
    
    function test_if_propper_data_is_correctly_handled () 
    {
        $result = $this->toarray->parse($this->testdata_1);
        $this->assertTrue(is_array($result), "The parsed result should be an array");
        $keys = array_keys($result);
        $this->assertTrue(count($keys) == 1, "The array should only have one root key");
        $this->assertTrue($keys[0] == 'topic', "The first key should be the topic");
        $this->assertTrue(array_key_exists('id',$result['topic']), "The array should contain an id");
        $this->assertTrue(array_key_exists('_content', $result['topic']['id']) , "Values should be stored in the _content key");
    } 
    
    /**
     * Node this function does not look for _valid_ xml
     * , just that it looks the same from the two outputengines.
     */
    function assertRightXml($xml) 
    {
        var_dump($xml);
    }
   
}

