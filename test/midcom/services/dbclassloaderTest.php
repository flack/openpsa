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
class midcom_services_dbclassloaderTest extends openpsa_testcase
{
    /**
     * @dataProvider providerGet_component_classes
     */
    public function testGet_component_classes($component, $result)
    {
        $classes = midcom::get()->dbclassloader->get_component_classes($component);
        $this->assertEquals($result, $classes);
    }

    public function providerGet_component_classes()
    {
        return [
            [
                'org.openpsa.projects',
                [
                    'org_openpsa_hour_report' => 'org_openpsa_projects_hour_report_dba',
                    'org_openpsa_project' => 'org_openpsa_projects_project',
                    'org_openpsa_task' => 'org_openpsa_projects_task_dba',
                    'org_openpsa_task_resource' => 'org_openpsa_projects_task_resource_dba',
                    'org_openpsa_task_status' => 'org_openpsa_projects_task_status_dba'
                ]
            ]
        ];
    }

    /**
     * @dataProvider providerGet_midcom_class_name_for_mgdschema_object
     */
    public function testGet_midcom_class_name_for_mgdschema_object($object, $result)
    {
        $component = midcom::get()->dbclassloader->get_midcom_class_name_for_mgdschema_object($object);
        $this->assertEquals($result, $component);
    }

    public function providerGet_midcom_class_name_for_mgdschema_object()
    {
        $ret = [
            [
                new org_openpsa_task,
                'org_openpsa_projects_task_dba'
            ],
            [
                new midgard_person,
                'midcom_db_person'
            ]
        ];
        if (class_exists('openpsa_person')) {
            $ret[] = [
                new openpsa_person,
                'midcom_db_person'
            ];
        }
        return $ret;
    }
}
