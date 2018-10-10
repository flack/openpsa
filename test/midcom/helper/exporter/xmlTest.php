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
        $this->assertInstanceOf(org_openpsa_projects_task_dba::class, $object);
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
        <approved>0</approved>
        <approver><![CDATA[{$object->metadata->approver}]]></approver>
        <authors><![CDATA[]]></authors>
        <created>0</created>
        <creator><![CDATA[{$object->metadata->creator}]]></creator>
        <deleted/>
        <exported>0</exported>
        <hidden/>
        <imported>0</imported>
        <isapproved/>
        <islocked/>
        <locked>0</locked>
        <locker><![CDATA[{$object->metadata->locker}]]></locker>
        <navnoentry/>
        <owner><![CDATA[]]></owner>
        <published>0</published>
        <revised>0</revised>
        <revision>0</revision>
        <revisor><![CDATA[{$object->metadata->revisor}]]></revisor>
        <scheduleend>0</scheduleend>
        <schedulestart>0</schedulestart>
        <score>0</score>
        <size>0</size>

    </midcom_helper_metadata>    <value><![CDATA[test

test]]></value>
    <style>33</style>
    <name><![CDATA[Test]]></name>
    <guid><![CDATA[]]></guid>
    <id>0</id>
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
        return [
            'midcom_helper_metadata' => [
                'guid' => 'a4de2d4263af11df90631192dd1df1f4f1f4'
            ],
            'guid' => 'a4de2d4263af11df90631192dd1df1f4f1f4',
            'invoiceableHours' => '',
            'agreement' => '102',
            'start' => '1274306400',
            'customer' => '57',
            'reportedHours' => '',
            'hoursInvoiceableDefault' => '1',
            'orgOpenpsaAccesstype' => '',
            'up' => '',
            'approvedHours' => '',
            'plannedHours' => '1',
            'description' => "test\n\nÜmläüt",
            'project' => '32',
            'id' => '352',
            'manager' => '',
            'title' => 'Test entry *.* 1',
            'orgOpenpsaObtype' => '6002',
            'priority' => '3',
            'status' => '6500',
            'invoicedHours' => '',
            'end' => '1305842399',
            'orgOpenpsaOwnerWg' => ''
        ];
    }
}
