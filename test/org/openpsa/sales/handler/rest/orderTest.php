<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\sales\handler\rest;

use midcom_db_person;
use openpsa_testcase;
use midcom;
use org_openpsa_products_product_dba;
use org_openpsa_products_product_group_dba;
use org_openpsa_sales_salesproject_dba;
use org_openpsa_sales_salesproject_deliverable_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class orderTest extends openpsa_testcase
{
    protected static midcom_db_person $_person;
    protected static org_openpsa_products_product_dba $_product;

    public static function setUpBeforeClass() : void
    {
        self::$_person = self::create_user(true);
        $product_group = self::create_class_object(org_openpsa_products_product_group_dba::class);
        self::$_product = self::create_class_object(org_openpsa_products_product_dba::class, [
            'productGroup' => $product_group->id,
            'delivery' => org_openpsa_products_product_dba::DELIVERY_SINGLE
        ]);
    }

    public function testHandler_create()
    {
        midcom::get()->auth->request_sudo('org.openpsa.sales');

        $this->set_post_data([
            'person_guid' => self::$_person->guid,
            'product_id' => self::$_product->id
        ]);

        $data = $this->run_handler('org.openpsa.sales', ['rest', 'order']);
        $this->assertEquals('sales_order_rest', $data['handler_id']);

        $response = $data['__openpsa_testcase_response'];
        $this->assertEquals(200, $response->getStatusCode());

        $deliverable = new org_openpsa_sales_salesproject_deliverable_dba(json_decode($response->getContent())->guid);
        $this->register_object($deliverable);
        $this->register_object(new org_openpsa_sales_salesproject_dba($deliverable->salesproject));

        $this->assertEquals(self::$_product->id, $deliverable->product);
        $this->assertEquals(org_openpsa_sales_salesproject_deliverable_dba::STATE_ORDERED, $deliverable->state);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_create_missing_params()
    {
        midcom::get()->auth->request_sudo('org.openpsa.sales');

        $this->set_post_data(['person_guid' => self::$_person->guid]);

        $data = $this->run_handler('org.openpsa.sales', ['rest', 'order']);
        $this->assertEquals(500, $data['__openpsa_testcase_response']->getStatusCode());

        midcom::get()->auth->drop_sudo();
    }
}
