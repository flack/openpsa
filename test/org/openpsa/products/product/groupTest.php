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
class org_openpsa_products_product_groupTest extends openpsa_testcase
{
    public function testCRUD()
    {
        $time = time();
        midcom::get()->auth->request_sudo('org.openpsa.products');
        $group = new org_openpsa_products_product_group_dba();
        $group->code = 'TEST-100' . $time;
        $group->_use_activitystream = false;
        $group->_use_rcs = false;
        $stat = $group->create();
        $this->assertTrue($stat);

        $this->assertEquals('TEST-100' . $time, $group->code);

        $group->code = 'TEST-101' . $time;
        $stat = $group->update();
        $this->assertTrue($stat);
        $this->register_object($group);
        $this->assertEquals('TEST-101' . $time, $group->code);

        $group2 = new org_openpsa_products_product_group_dba();
        $group2->code = 'TEST-101' . $time;
        $stat = $group2->create();
        $this->assertFalse($stat);
        $this->assertEquals(midcom_connection::get_error(), MGD_ERR_OBJECT_NAME_EXISTS);

        $stat = $group->delete();
        $this->assertTrue($stat);

        midcom::get()->auth->drop_sudo();
    }
}
