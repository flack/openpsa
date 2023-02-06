<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\products;

use openpsa_testcase;
use midcom;
use org_openpsa_products_product_group_dba;
use org_openpsa_products_product_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class productTest extends openpsa_testcase
{
    protected static org_openpsa_products_product_group_dba $_group;

    public static function setUpBeforeClass() : void
    {
        self::$_group = self::create_class_object(org_openpsa_products_product_group_dba::class, ['code' => 'TEST-' . __CLASS__]);
    }

    public function testCRUD()
    {
        $code = 'PRODUCT-TEST-' . __CLASS__ . time();
        $product = new org_openpsa_products_product_dba();
        $product->code = $code;
        $product->productGroup = self::$_group->id;
        $product->_use_rcs = false;

        midcom::get()->auth->request_sudo('org.openpsa.products');
        $stat = $product->create();
        $this->assertTrue($stat);
        $this->register_object($product);

        $parent = $product->get_parent();
        $this->assertEquals($parent->guid, self::$_group->guid);

        $product->title = 'TEST TITLE';
        $stat = $product->update();
        $this->assertTrue($stat);

        $this->assertEquals('TEST TITLE', $product->title);

        $stat = $product->delete();
        $this->assertTrue($stat);

        midcom::get()->auth->drop_sudo();
    }
}
