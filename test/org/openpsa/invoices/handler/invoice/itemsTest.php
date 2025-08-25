<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\invoices\handler\invoice;

use midcom_db_person;
use openpsa_testcase;
use org_openpsa_invoices_invoice_dba;
use midcom;
use org_openpsa_invoices_invoice_item_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class itemsTest extends openpsa_testcase
{
    protected static midcom_db_person $_person;
    protected static org_openpsa_invoices_invoice_dba $_invoice;

    public static function setUpBeforeClass() : void
    {
        self::$_person = self::create_user(true);
        self::$_invoice = self::create_class_object(org_openpsa_invoices_invoice_dba::class);
        self::create_class_object(org_openpsa_invoices_invoice_item_dba::class, ['invoice' => self::$_invoice->id]);
    }

    public function testHandler_items()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');

        $data = $this->run_handler('org.openpsa.invoices', ['invoice', 'items', self::$_invoice->guid]);
        $this->assertEquals('invoice_items', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_recalculation()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');
        $this->set_post_data([]);
        $url = $this->run_relocate_handler('org.openpsa.invoices', ['invoice', 'recalculation', self::$_invoice->guid]);
        $this->assertEquals('invoice/items/' . self::$_invoice->guid . '/', $url);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_itemedit()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');
        $item = $this->create_object(org_openpsa_invoices_invoice_item_dba::class, ['invoice' => self::$_invoice->id]);

        $_POST = [
            'oper' => 'edit',
            'id' => $item->id,
            'description' => 'TEST DESCRIPTION',
            'price' => 20,
            'quantity' => 10
        ];

        $data = $this->run_handler('org.openpsa.invoices', ['invoice', 'itemedit', self::$_invoice->guid]);
        $this->assertEquals('invoice_item_edit', $data['handler_id']);

        $item->refresh();
        $this->assertEquals(20, $item->pricePerUnit);
        $this->assertEquals(10, $item->units);
        $this->assertEquals('TEST DESCRIPTION', $item->description);

        midcom::get()->auth->drop_sudo();
    }
}
