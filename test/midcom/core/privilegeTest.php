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
class midcom_core_privilegeTest extends openpsa_testcase
{
    protected static $_person;
    protected static $_project;

    /**
     * @dataProvider providerStoreArray
     */
    public function testStoreArray($input, $output)
    {
        $_MIDCOM->auth->request_sudo('midcom.core');

        $privilege = new midcom_core_privilege($input);
        $stat = $privilege->store();

        $this->assertEquals($output['stat'], $stat);

        foreach ($output as $field => $value)
        {
            if ($field == 'stat')
            {
                continue;
            }
            $this->assertEquals($value, $privilege->$field, 'Difference in field ' . $field);
        }

        $stat = $privilege->drop();
        $this->assertTrue($stat);

        $_MIDCOM->auth->drop_sudo();
    }

    public function providerStoreArray()
    {
        self::$_person = self::create_class_object('midcom_db_person');
        self::$_project = self::create_class_object('org_openpsa_projects_project');

        return array
        (
            1 => array
            (
                'input' => array
                (
                    'assignee' => 'user:' . self::$_person->guid,
                    'privilegename' => 'midgard:read',
                    'objectguid' => self::$_project->guid,
                    'value' => MIDCOM_PRIVILEGE_ALLOW,
                ),
                'output' => array
                (
                    'stat' => true,
                    'privilegename' => 'midgard:read'
                )
            )
        );
    }
}
?>