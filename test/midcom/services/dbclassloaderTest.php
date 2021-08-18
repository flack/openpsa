<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\services;

use PHPUnit\Framework\TestCase;
use midcom;
use midcom_db_person;
use midcom_services_at_entry_db;
use midcom_services_dbclassloader;
use midgard_person;
use openpsa_person;
use org_openpsa_projects_task_dba;
use org_openpsa_task;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class dbclassloaderTest extends TestCase
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
                    'org_openpsa_project' => 'org_openpsa_projects_project',
                    'org_openpsa_role' => 'org_openpsa_projects_role_dba',
                    'org_openpsa_task' => 'org_openpsa_projects_task_dba',
                    'org_openpsa_task_resource' => 'org_openpsa_projects_task_resource_dba',
                    'org_openpsa_task_status' => 'org_openpsa_projects_task_status_dba'
                ]
            ]
        ];
    }

    /**
     * @dataProvider provider_class_conversion
     */
    public function test_class_conversion(string $midcom, string $midgard)
    {
        $cl = new midcom_services_dbclassloader();
        $result = $cl->get_mgdschema_class_name_for_midcom_class($midcom);
        $this->assertEquals($midgard, $result);

        $result = $cl->get_midcom_class_name_for_mgdschema_object(new $midgard);
        $this->assertEquals($midcom, $result);
        $result = $cl->get_midcom_class_name_for_mgdschema_object($midgard);
        $this->assertEquals($midcom, $result);
    }

    public function provider_class_conversion()
    {
        return [
            [
                org_openpsa_projects_task_dba::class,
                org_openpsa_task::class
            ],
            [
                midcom_db_person::class,
                midcom::get()->config->get('person_class')
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
            ],
            [
                new midcom_services_at_entry_db,
                'midcom_services_at_entry_dba'
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
