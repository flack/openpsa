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
class org_openpsa_sales_handler_deliverable_processTest extends openpsa_testcase
{
    protected static $_person;
    protected static $_salesproject;
    protected static $_product;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
        self::$_salesproject = self::create_class_object(org_openpsa_sales_salesproject_dba::class, ['customerContact' => self::$_person->id]);
        $product_group = self::create_class_object(org_openpsa_products_product_group_dba::class);
        $product_attributes = [
            'productGroup' => $product_group->id,
            'name' => 'TEST_' . __CLASS__ . '_' . time(),
        ];
        self::$_product = self::create_class_object(org_openpsa_products_product_dba::class, $product_attributes);
    }

    public function testHandler_process_single()
    {
        midcom::get()->auth->request_sudo('org.openpsa.sales');

        $product = org_openpsa_products_product_dba::get_cached(self::$_product->id);
        $product->delivery = org_openpsa_products_product_dba::DELIVERY_SINGLE;
        $product->update();

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
            'order' => true,
        ];

        $url = $this->run_relocate_handler('org.openpsa.sales', ['deliverable', 'process', $deliverable->guid]);
        $this->assertEquals($url, 'salesproject/' . self::$_salesproject->guid . '/');
        $deliverable->refresh();
        $this->assertEquals(org_openpsa_sales_salesproject_deliverable_dba::STATE_ORDERED, $deliverable->state);

        $_POST = [
            'deliver' => true,
        ];
        $url = $this->run_relocate_handler('org.openpsa.sales', ['deliverable', 'process', $deliverable->guid]);
        $this->assertEquals($url, 'salesproject/' . self::$_salesproject->guid . '/');
        $deliverable->refresh();
        $this->assertEquals(org_openpsa_sales_salesproject_deliverable_dba::STATE_DELIVERED, $deliverable->state);

        $_POST = [
            'invoice' => true,
        ];
        $url = $this->run_relocate_handler('org.openpsa.sales', ['deliverable', 'process', $deliverable->guid]);
        $this->assertEquals($url, 'salesproject/' . self::$_salesproject->guid . '/');
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

        $product = org_openpsa_products_product_dba::get_cached(self::$_product->id);
        $product->delivery = org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION;
        $product->update();

        $deliverable_attributes = [
            'salesproject' => self::$_salesproject->id,
            'product' => self::$_product->id,
            'start' => time(),
            'continuous' => true,
            'unit' => 'q',
            'orgOpenpsaObtype' => org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION,
        ];

        $deliverable = $this->create_object(org_openpsa_sales_salesproject_deliverable_dba::class, $deliverable_attributes);
        $deliverable->update();

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $_POST = [
            'order' => true,
        ];

        $url = $this->run_relocate_handler('org.openpsa.sales', ['deliverable', 'process', $deliverable->guid]);
        $this->assertEquals($url, 'salesproject/' . self::$_salesproject->guid . '/');
        $deliverable->refresh();
        $this->assertEquals(org_openpsa_sales_salesproject_deliverable_dba::STATE_ORDERED, $deliverable->state);

        $mc = new org_openpsa_relatedto_collector($deliverable->guid, 'midcom_services_at_entry_dba');
        $mc->add_object_order('start', 'DESC');
        $at_entries = $mc->get_related_objects();

        $this->register_objects($at_entries);
        $this->assertCount(1, $at_entries);

        $_POST = [
            'run_cycle' => true,
            'at_entry' => $at_entries[0]->guid
        ];

        $url = $this->run_relocate_handler('org.openpsa.sales', ['deliverable', 'process', $deliverable->guid]);
        $this->assertEquals($url, 'salesproject/' . self::$_salesproject->guid . '/');
        $deliverable->refresh();

        $mc = new org_openpsa_relatedto_collector($deliverable->guid, 'midcom_services_at_entry_dba');
        $mc->add_object_order('start', 'DESC');
        $at_entries = $mc->get_related_objects();

        $this->register_objects($at_entries);
        $this->assertCount(1, $at_entries);

        midcom::get()->auth->drop_sudo();
    }
}
