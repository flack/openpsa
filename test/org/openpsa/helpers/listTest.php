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
class org_openpsa_helpers_listTest extends openpsa_testcase
{
    public function test_task_groups()
    {
        $org = $this->create_object('org_openpsa_contacts_group_dba');
        $org->refresh();
        $person = $this->create_object('midcom_db_person');
        $this->create_object('midcom_db_member', ['uid' => $person->id, 'gid' => $org->id]);
        $salesproject = $this->create_object('org_openpsa_sales_salesproject_dba', ['customerContact' => $person->id]);

        midcom::get()->auth->request_sudo('org.openpsa.helpers');

        $result = org_openpsa_helpers_list::task_groups($salesproject);

        $expected = [
            0 => '',
            $org->id => $org->get_label()
        ];

        $this->assertEquals($expected, $result);

        midcom::get()->auth->drop_sudo();
    }
}
