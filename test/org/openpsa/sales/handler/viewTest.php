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
class org_openpsa_sales_handler_viewTest extends openpsa_testcase
{
    protected static $_person;
    protected static $_salesproject;
    protected static $_product;
    protected static $_product_subscription;

    public static function setUpBeforeClass() : void
    {
        self::$_person = self::create_user(true);
        self::$_salesproject = self::create_class_object(org_openpsa_sales_salesproject_dba::class, ['customerContact' => self::$_person->id]);
        $product_group = self::create_class_object(org_openpsa_products_product_group_dba::class);
        $product_attributes = [
            'productGroup' => $product_group->id,
            'name' => 'TEST_' . __CLASS__ . '_' . time(),
            'delivery' => org_openpsa_products_product_dba::DELIVERY_SINGLE
        ];
        self::$_product = self::create_class_object(org_openpsa_products_product_dba::class, $product_attributes);

        $product_attributes['delivery'] = org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION;
        $product_attributes['name'] .= 'subscription';

        self::$_product_subscription = self::create_class_object(org_openpsa_products_product_dba::class, $product_attributes);
    }

    public function testHandler_view()
    {
        midcom::get()->auth->request_sudo('org.openpsa.sales');

        $deliverable_attributes = [
            'salesproject' => self::$_salesproject->id,
            'product' => self::$_product_subscription->id,
            'state' => org_openpsa_sales_salesproject_deliverable_dba::STATE_ORDERED
        ];

        $this->create_object(org_openpsa_sales_salesproject_deliverable_dba::class, $deliverable_attributes);

        $data = $this->run_handler('org.openpsa.sales', ['salesproject', self::$_salesproject->guid]);
        $this->assertEquals('salesproject_view', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }


    public function testHandler_action_single()
    {
        midcom::get()->auth->request_sudo('org.openpsa.sales');

        $deliverable_attributes = [
            'salesproject' => self::$_salesproject->id,
            'product' => self::$_product->id,
            'orgOpenpsaObtype' => org_openpsa_products_product_dba::DELIVERY_SINGLE,
            'units' => 1,
            'pricePerUnit' => 5
        ];

        $deliverable = $this->create_object(org_openpsa_sales_salesproject_deliverable_dba::class, $deliverable_attributes);

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $_POST = [
            'id' => $deliverable->id,
        ];

        $data = $this->run_handler('org.openpsa.sales', ['salesproject', 'action', 'order']);
        $this->assertInstanceOf(midcom_response_json::class, $data['__openpsa_testcase_response']);
        $this->assertTrue($data['__openpsa_testcase_response']->success);
        $deliverable->refresh();
        $this->assertEquals(org_openpsa_sales_salesproject_deliverable_dba::STATE_ORDERED, $deliverable->state);

        $_POST = [
            'id' => $deliverable->id,
        ];

        $data = $this->run_handler('org.openpsa.sales', ['salesproject', 'action', 'deliver']);
        $this->assertInstanceOf(midcom_response_json::class, $data['__openpsa_testcase_response']);
        $this->assertTrue($data['__openpsa_testcase_response']->success);
        $deliverable->refresh();
        $this->assertEquals(org_openpsa_sales_salesproject_deliverable_dba::STATE_DELIVERED, $deliverable->state);

        $_POST = [
            'id' => $deliverable->id,
        ];

        $data = $this->run_handler('org.openpsa.sales', ['salesproject', 'action', 'invoice']);
        $this->assertInstanceOf(midcom_response_json::class, $data['__openpsa_testcase_response']);
        $this->assertTrue($data['__openpsa_testcase_response']->success);
        $deliverable->refresh();
        $this->assertEquals(org_openpsa_sales_salesproject_deliverable_dba::STATE_INVOICED, $deliverable->state);

        $qb = org_openpsa_invoices_invoice_item_dba::new_query_builder();
        $qb->add_constraint('deliverable', '=', $deliverable->id);
        $items = $qb->execute();
        $this->register_objects($items);
        $this->assertCount(1, $items);
        $invoice = new org_openpsa_invoices_invoice_dba($items[0]->invoice);
        $this->register_object($invoice);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_process_subscription()
    {
        midcom::get()->auth->request_sudo('org.openpsa.sales');

        $deliverable_attributes = [
            'salesproject' => self::$_salesproject->id,
            'product' => self::$_product_subscription->id,
            'start' => time(),
            'continuous' => true,
            'unit' => 'q',
            'orgOpenpsaObtype' => org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION,
        ];

        $deliverable = $this->create_object(org_openpsa_sales_salesproject_deliverable_dba::class, $deliverable_attributes);
        $deliverable->update();

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $_POST = [
            'id' => $deliverable->id,
        ];

        $data = $this->run_handler('org.openpsa.sales', ['salesproject', 'action', 'order']);
        $this->assertInstanceOf(midcom_response_json::class, $data['__openpsa_testcase_response']);
        $this->assertTrue($data['__openpsa_testcase_response']->success);
        $deliverable->refresh();
        $this->assertEquals(org_openpsa_sales_salesproject_deliverable_dba::STATE_ORDERED, $deliverable->state);

        $mc = new org_openpsa_relatedto_collector($deliverable->guid, 'midcom_services_at_entry_dba');
        $mc->add_object_order('start', 'DESC');
        $at_entries = $mc->get_related_objects();

        $this->register_objects($at_entries);
        $this->assertCount(1, $at_entries);

        $_POST = [
            'id' => $deliverable->id,
        ];

        $data = $this->run_handler('org.openpsa.sales', ['salesproject', 'action', 'run_cycle']);
        $this->assertInstanceOf(midcom_response_json::class, $data['__openpsa_testcase_response']);
        $this->assertTrue($data['__openpsa_testcase_response']->success);
        $deliverable->refresh();

        $mc = new org_openpsa_relatedto_collector($deliverable->guid, 'midcom_services_at_entry_dba');
        $mc->add_object_order('start', 'DESC');
        $at_entries = $mc->get_related_objects();

        $this->register_objects($at_entries);
        $this->assertCount(1, $at_entries);

        midcom::get()->auth->drop_sudo();
    }
}
