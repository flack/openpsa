<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\core;

use openpsa_testcase;
use midcom;
use midcom_db_person;
use org_openpsa_projects_project;
use midcom_core_privilege;
use midcom_connection;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class privilegeTest extends openpsa_testcase
{
    /**
     * @dataProvider providerStoreArray
     */
    public function testStoreArray($input, $output)
    {
        midcom::get()->auth->request_sudo('midcom.core');

        $person = $this->create_object(midcom_db_person::class);
        $project = $this->create_object(org_openpsa_projects_project::class);
        $input['assignee'] = 'user:' . $person->guid;
        $input['objectguid'] = $project->guid;

        $privilege = new midcom_core_privilege($input);
        $stat = $privilege->store();

        $this->assertEquals($output['stat'], $stat, midcom_connection::get_error_string());

        foreach ($output as $field => $value) {
            if ($field == 'stat') {
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
        return [
            1 => [
                'input' => [
                    'privilegename' => 'midgard:read',
                    'value' => MIDCOM_PRIVILEGE_ALLOW,
                ],
                'output' => [
                    'stat' => true,
                    'privilegename' => 'midgard:read'
                ]
            ]
        ];
    }
}
