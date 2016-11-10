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
class org_openpsa_contacts_groupTest extends openpsa_testcase
{
    public function testCRUD()
    {
        midcom::get()->auth->request_sudo('org.openpsa.contacts');
        $group = new org_openpsa_contacts_group_dba();
        $time = time();
        $group->name = 'TEST NAME' . $time;
        $group->_use_activitystream = false;
        $group->_use_rcs = false;

        $stat = $group->create();
        $this->assertFalse($stat);

        $group->name = 'TEST-NAME' . $time;
        $stat = $group->create();
        $this->assertTrue($stat);
        $this->register_object($group);
        $this->assertEquals('TEST-NAME' . $time, $group->get_label());
        $this->assertEquals(org_openpsa_core_acl::ACCESS_PUBLIC, $group->orgOpenpsaAccesstype);

        $group->official = 'TEST OFFICIAL';
        $stat = $group->update();
        $this->assertTrue($stat);
        $this->assertEquals('TEST OFFICIAL', $group->get_label());

        $stat = $group->delete();
        $this->assertTrue($stat);

        midcom::get()->auth->drop_sudo();
    }
}
