<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

require_once('rootfile.php');

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class org_openpsa_products_product_groupTest extends openpsa_testcase
{
    public function testCRUD()
    {
        $_MIDCOM->auth->request_sudo('org.openpsa.products');
        $group = new org_openpsa_products_product_group_dba();
        $group->code = 'TEST-100';
        $stat = $group->create();
        $this->assertTrue($stat);

        $this->assertEquals($group->code, 'TEST-100');

        $group->code = 'TEST-101';
        $stat = $group->update();
        $this->assertTrue($stat);
        $this->assertEquals($group->code, 'TEST-101');

        $group2 = new org_openpsa_products_product_group_dba();
        $group2->code = 'TEST-101';
        $stat = $group2->create();
        $this->assertFalse($stat);
        $this->assertEquals(midcom_connection::get_error(), MGD_ERR_OBJECT_NAME_EXISTS);

        $stat = $group->delete();
        $this->assertTrue($stat);

        $_MIDCOM->auth->drop_sudo();
    }
}
?>