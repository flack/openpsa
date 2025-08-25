<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\sales\handler\deliverable;

use midcom_db_person;
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
class addTest extends openpsa_testcase
{
    protected static midcom_db_person $_person;

    protected org_openpsa_sales_salesproject_dba $_salesproject;
    protected org_openpsa_products_product_dba $_product;

    public static function setUpBeforeClass() : void
    {
        self::$_person = self::create_user(true);
    }

    public function setUp() : void
    {
        $this->_salesproject = $this->create_object(org_openpsa_sales_salesproject_dba::class);
        $product_group = $this->create_object(org_openpsa_products_product_group_dba::class);
        $product_attributes = [
            'productGroup' => $product_group->id,
            'code' => 'TEST_' . __CLASS__ . '_' . time(),
            'unit' => 'm'
        ];
        $this->_product = $this->create_object(org_openpsa_products_product_dba::class, $product_attributes);

        $this->set_post_data([
            'product' => $this->_product->id,
        ]);
    }

    public function testHandler_add_create()
    {
        midcom::get()->auth->request_sudo('org.openpsa.sales');

        $data = $this->run_handler('org.openpsa.sales', ['deliverable', 'add', $this->_salesproject->guid]);
        $this->assertEquals('deliverable_add', $data['handler_id']);

        $formdata = [
            'title' => 'TEST ' . __CLASS__ . '_' . time(),
            'plannedUnits' => '1',
        ];
        $this->set_dm_formdata($data['controller'], $formdata);
        $data = $this->run_handler('org.openpsa.sales', ['deliverable', 'add', $this->_salesproject->guid]);
        $url = $this->get_dialog_url($data);

        $this->assertEquals('salesproject/' . $this->_salesproject->guid . '/', $url);

        $qb = org_openpsa_sales_salesproject_deliverable_dba::new_query_builder();
        $qb->add_constraint('salesproject', '=', $this->_salesproject->id);
        $results = $qb->execute();
        $this->assertCount(1, $results);
        $this->register_object($results[0]);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_add_create_subscription()
    {
        midcom::get()->auth->request_sudo('org.openpsa.sales');

        $this->_product->delivery = org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION;
        $this->_product->update();

        $data = $this->run_handler('org.openpsa.sales', ['deliverable', 'add', $this->_salesproject->guid]);

        $formdata = [
            'title' => 'TEST ' . __CLASS__ . '_' . time(),
            'continuous' => true,
            'start' => ['date' => date('Y-m-d')],
            'plannedUnits' => '1'
        ];

        $this->set_dm_formdata($data['controller'], $formdata);
        $data = $this->run_handler('org.openpsa.sales', ['deliverable', 'add', $this->_salesproject->guid]);
        $url = $this->get_dialog_url($data);

        $this->assertEquals('salesproject/' . $this->_salesproject->guid . '/', $url);

        $qb = org_openpsa_sales_salesproject_deliverable_dba::new_query_builder();
        $qb->add_constraint('salesproject', '=', $this->_salesproject->id);
        $results = $qb->execute();
        $this->assertCount(1, $results);

        $deliverable = $results[0];
        $this->register_object($deliverable);
        $this->assertEquals(org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION, $deliverable->orgOpenpsaObtype);

        midcom::get()->auth->drop_sudo();
    }
}
