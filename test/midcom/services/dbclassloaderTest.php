<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR);
    require_once(OPENPSA_TEST_ROOT . 'rootfile.php');
}

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_services_at_dbclassloaderTest extends openpsa_testcase
{
    /**
     * @dataProvider providerGet_component_classes
     */
    public function testGet_component_classes($component, $result)
    {
        $classes = $_MIDCOM->dbclassloader->get_component_classes($component);
        $this->assertEquals($result, $classes);
    }

    public function providerGet_component_classes()
    {
        return array
        (
            array
            (
                'org.openpsa.projects',
                array
                (
                    'org_openpsa_hour_report' => 'org_openpsa_projects_hour_report_dba',
                    'org_openpsa_task' => 'org_openpsa_projects_task_dba',
                    'org_openpsa_task_resource' => 'org_openpsa_projects_task_resource_dba',
                    'org_openpsa_task_status' => 'org_openpsa_projects_task_status_dba'
                )
            )
        );
    }

    /**
     * @dataProvider providerGet_midcom_class_name_for_mgdschema_object
     */
    public function testGet_midcom_class_name_for_mgdschema_object($object, $result)
    {
        $component = $_MIDCOM->dbclassloader->get_midcom_class_name_for_mgdschema_object($object);
        $this->assertEquals($result, $component);
    }

    public function providerGet_midcom_class_name_for_mgdschema_object()
    {
        return array
        (
            array
            (
                new org_openpsa_task,
                'org_openpsa_projects_task_dba'
            )
        );
    }
}
?>