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
class org_openpsa_relatedto_dbaTest extends openpsa_testcase
{
    public function testCRUD()
    {
        $relatedto = new org_openpsa_relatedto_dba();

        midcom::get()->auth->request_sudo('org.openpsa.relatedto');
        $stat = $relatedto->create();
        $this->assertTrue($stat);
        $this->assertEquals(org_openpsa_relatedto_dba::SUSPECTED, $relatedto->status);

        $relatedto->status = org_openpsa_relatedto_dba::CONFIRMED;
        $stat = $relatedto->update();
        $this->assertTrue($stat);
        $this->assertEquals(org_openpsa_relatedto_dba::CONFIRMED, $relatedto->status);

        $stat = $relatedto->delete();
        $this->assertTrue($stat);

        midcom::get()->auth->drop_sudo();
    }
}
