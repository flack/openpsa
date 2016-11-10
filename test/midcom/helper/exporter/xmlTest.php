<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

require_once __DIR__ . '/../__files/xml_constraint.php';

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_helper_exporter_xmlTest extends openpsa_testcase
{
    public function test_data2array()
    {
        $data = file_get_contents(__DIR__ . '/../__files/task.xml');
        $mapper = new midcom_helper_exporter_xml();
        $array = $mapper->data2array($data);
        $expected = $this->_get_data_array();
        $this->assertEquals($expected, $array);
    }

    public function test_data2object()
    {
        $data = $this->_get_data_array();
        $mapper = new midcom_helper_exporter_xml();
        $object = new org_openpsa_projects_task_dba;
        $object = $mapper->data2object($data, $object);
        $this->assertInstanceOf('org_openpsa_projects_task_dba', $object);
        $this->assertEquals("test\n\nÜmläüt", $object->description);
        $this->assertEquals(32, $object->project);
    }

    public function test_object2data()
    {
        $mapper = new midcom_helper_exporter_xml();
        $object = new midcom_db_element;
        $object->value = "test\n\ntest";
        $object->style = 33;
        $object->name = 'Test';

        $string = $mapper->object2data($object);

        $expected = <<<EOX
<midgard_element>
    <midcom_helper_metadata>
        <guid><![CDATA[]]></guid>
    </midcom_helper_metadata>    <value><![CDATA[test

test]]></value>
    <style>33</style>
    <name><![CDATA[Test]]></name>
    <guid><![CDATA[]]></guid>
    <sitegroup>1</sitegroup>
    <action><![CDATA[]]></action>
    <id>0</id>
    <sid>0</sid>
    <lang>0</lang>
</midgard_element>
EOX;

        $this->assert_xml_data($expected, $string);
    }

    private function assert_xml_data($expected, $actual)
    {
        $constraint = new xml_comparison($expected);
        self::assertThat($actual, $constraint);
    }

    private function _get_data_array()
    {
        return array(
            'midcom_helper_metadata' => array(
                'guid' => 'a4de2d4263af11df90631192dd1df1f4f1f4'
            ),
            'guid' => 'a4de2d4263af11df90631192dd1df1f4f1f4',
            'sitegroup' => '1',
            'action' => '',
            'invoiceableHours' => '',
            'projectCode' => '',
            'agreement' => '102',
            'start' => '1274306400',
            'customer' => '57',
            'reportedHours' => '',
            'hoursInvoiceableDefault' => '1',
            'expensesInvoiceableDefault' => '',
            'orgOpenpsaAccesstype' => '',
            'up' => '',
            'approvedHours' => '',
            'maxCost' => '',
            'pricePlugin' => '',
            'maxPrice' => '',
            'plannedHours' => '1',
            'dependency' => '',
            'description' => "test\n\nÜmläüt",
            'project' => '32',
            'id' => '352',
            'acceptanceType' => '',
            'manager' => '',
            'priceBase' => '',
            'title' => 'Test entry *.* 1',
            'orgOpenpsaObtype' => '6002',
            'affectsSaldo' => '',
            'costCache' => '',
            'priority' => '3',
            'costPlugin' => '',
            'priceCache' => '',
            'costBase' => '',
            'orgOpenpsaWgtype' => '',
            'status' => '6500',
            'invoicedHours' => '',
            'end' => '1305842399',
            'orgOpenpsaOwnerWg' => ''
        );
    }
}
