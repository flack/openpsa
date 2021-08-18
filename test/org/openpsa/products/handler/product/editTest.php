<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\products\handler\product;

use openpsa_testcase;
use midcom;
use org_openpsa_products_product_group_dba;
use org_openpsa_products_product_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class editTest extends openpsa_testcase
{
    protected static $_product;

    public static function setUpBeforeClass() : void
    {
        $group = self::create_class_object(org_openpsa_products_product_group_dba::class, ['code' => 'TEST_' . __CLASS__ . time()]);
        self::$_product = self::create_class_object(org_openpsa_products_product_dba::class, ['productGroup' => $group->id]);
    }

    public function testHandler_edit()
    {
        midcom::get()->auth->request_sudo('org.openpsa.products');

        $data = $this->run_handler('org.openpsa.products', ['product', 'edit', self::$_product->guid]);
        $this->assertEquals('edit_product', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
