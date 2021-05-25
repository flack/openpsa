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
class org_openpsa_directmarketing_campaign_memberTest extends openpsa_testcase
{
    public function testCRUD()
    {
        midcom::get()->auth->request_sudo('org.openpsa.directmarketing');

        $member = new org_openpsa_directmarketing_campaign_member_dba();

        $stat = $member->create();
        $this->assertTrue($stat, midcom_connection::get_error_string());
        $this->register_object($member);

        $member->suspended = 1;

        $stat = $member->update();
        $this->assertTrue($stat);
        $member->refresh();

        $this->assertEquals(1, $member->suspended);

        $stat = $member->delete();
        $this->assertTrue($stat);

        midcom::get()->auth->drop_sudo();
    }
}
