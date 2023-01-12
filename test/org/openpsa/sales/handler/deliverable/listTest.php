<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\sales\handler\deliverable;

use openpsa_testcase;
use midcom;
use org_openpsa_sales_salesproject_dba;
use org_openpsa_products_product_group_dba;
use org_openpsa_products_product_dba;
use org_openpsa_sales_salesproject_deliverable_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class listTest extends openpsa_testcase
{
    protected static $_person;

    public static function setUpBeforeClass() : void
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_list()
    {
        $product_group = $this->create_object(org_openpsa_products_product_group_dba::class);
        $product_attributes = [
            'productGroup' => $product_group->id,
            'code' => 'TEST_' . __CLASS__ . '_' . time(),
        ];
        $product = $this->create_object(org_openpsa_products_product_dba::class, $product_attributes);
        $salesproject = $this->create_object(org_openpsa_sales_salesproject_dba::class);
        $this->create_object(org_openpsa_sales_salesproject_deliverable_dba::class, [
            'salesproject' => $salesproject->id,
            'product' => $product->id
        ]);

        midcom::get()->auth->request_sudo('org.openpsa.sales');
        $data = $this->run_handler('org.openpsa.sales', ['deliverable', 'list', 'product', $product->guid]);
        midcom::get()->auth->drop_sudo();
        $this->assertEquals('deliverable_list_product', $data['handler_id']);

    }
}
