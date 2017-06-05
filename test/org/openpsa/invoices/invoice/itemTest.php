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
class org_openpsa_invoices_invoice_itemTest extends openpsa_testcase
{
    protected static $_invoice;
    protected static $_salesproject;
    protected static $_deliverable;

    public static function setUpBeforeClass()
    {
        self::$_invoice = self::create_class_object('org_openpsa_invoices_invoice_dba');
        self::$_salesproject = self::create_class_object('org_openpsa_sales_salesproject_dba');
        $attributes = [
            'salesproject' => self::$_salesproject->id
        ];
        self::$_deliverable = self::create_class_object('org_openpsa_sales_salesproject_deliverable_dba', $attributes);
    }

    public function testCRUD()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');
        $item = new org_openpsa_invoices_invoice_item_dba();
        $item->invoice = self::$_invoice->id;
        $item->deliverable = self::$_deliverable->id;
        $item->pricePerUnit = 100;
        $item->units = 2.5;
        $stat = $item->create();
        $this->assertTrue($stat);
        $this->register_object($item);

        $parent = $item->get_parent();
        $this->assertEquals($parent->guid, self::$_invoice->guid);

        self::$_invoice->refresh();
        self::$_deliverable->refresh();
        $this->assertEquals(self::$_invoice->sum, 250);
        $this->assertEquals(self::$_deliverable->invoiced, 250);
        $this->assertEquals(self::$_deliverable->state, org_openpsa_sales_salesproject_deliverable_dba::STATE_INVOICED);

        $item->units = 3.5;
        $stat = $item->update();
        $this->assertTrue($stat);

        self::$_invoice->refresh();
        self::$_deliverable->refresh();
        $this->assertEquals(self::$_invoice->sum, 350);
        $this->assertEquals(self::$_deliverable->invoiced, 350);

        $stat = $item->delete();
        $this->assertTrue($stat);

        self::$_invoice->refresh();
        self::$_deliverable->refresh();
        $this->assertEquals(self::$_invoice->sum, 0);
        $this->assertEquals(self::$_deliverable->invoiced, 0);

        midcom::get()->auth->drop_sudo();
    }

    public function tearDown()
    {
        self::delete_linked_objects('org_openpsa_invoices_invoice_item_dba', 'invoice', self::$_invoice->id);
        parent::tearDown();
    }
}
