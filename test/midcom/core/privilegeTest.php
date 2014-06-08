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
class midcom_core_privilegeTest extends openpsa_testcase
{
    protected $_person;
    protected $_project;

    /**
     * @dataProvider providerStoreArray
     */
    public function testStoreArray($input, $output)
    {
        midcom::get()->auth->request_sudo('midcom.core');

        $privilege = new midcom_core_privilege($input);
        $stat = $privilege->store();

        $this->assertEquals($output['stat'], $stat, midcom_connection::get_error_string());

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

        midcom::get()->auth->drop_sudo();
    }

    public function providerStoreArray()
    {
        $this->_person = $this->create_object('midcom_db_person');
        $this->_project = $this->create_object('org_openpsa_projects_project');

        return array
        (
            1 => array
            (
                'input' => array
                (
                    'assignee' => 'user:' . $this->_person->guid,
                    'privilegename' => 'midgard:read',
                    'objectguid' => $this->_project->guid,
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