<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\invoices\invoice;

use openpsa_testcase;
use midcom;
use org_openpsa_invoices_invoice_dba;
use org_openpsa_sales_salesproject_dba;
use org_openpsa_sales_salesproject_deliverable_dba;
use org_openpsa_invoices_invoice_item_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class itemTest extends openpsa_testcase
{
    protected static $_invoice;
    protected static $_deliverable;

    public static function setUpBeforeClass() : void
    {
        self::$_invoice = self::create_class_object(org_openpsa_invoices_invoice_dba::class);
        $salesproject = self::create_class_object(org_openpsa_sales_salesproject_dba::class);
        $attributes = [
            'salesproject' => $salesproject->id
        ];
        self::$_deliverable = self::create_class_object(org_openpsa_sales_salesproject_deliverable_dba::class, $attributes);
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
        $this->assertEquals(self::$_invoice->guid, $parent->guid);

        self::$_invoice->refresh();
        self::$_deliverable->refresh();
        $this->assertEquals(250, self::$_invoice->sum);
        $this->assertEquals(250, self::$_deliverable->invoiced);
        $this->assertEquals(org_openpsa_sales_salesproject_deliverable_dba::STATE_INVOICED, self::$_deliverable->state);

        $item->units = 3.5;
        $stat = $item->update();
        $this->assertTrue($stat);

        self::$_invoice->refresh();
        self::$_deliverable->refresh();
        $this->assertEquals(350, self::$_invoice->sum);
        $this->assertEquals(350, self::$_deliverable->invoiced);

        $stat = $item->delete();
        $this->assertTrue($stat);

        self::$_invoice->refresh();
        self::$_deliverable->refresh();
        $this->assertEquals(0, self::$_invoice->sum);
        $this->assertEquals(0, self::$_deliverable->invoiced);

        midcom::get()->auth->drop_sudo();
    }

    public function tearDown() : void
    {
        self::delete_linked_objects('org_openpsa_invoices_invoice_item_dba', 'invoice', self::$_invoice->id);
        parent::tearDown();
    }
}
