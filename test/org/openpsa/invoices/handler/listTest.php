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
class org_openpsa_invoices_handler_listTest extends openpsa_testcase
{
    protected static $_person;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_dashboard()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');

        $data = $this->run_handler('org.openpsa.invoices', []);
        $this->assertEquals('dashboard', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_json()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');

        $data = $this->run_handler('org.openpsa.invoices', ['list', 'json', 'all']);
        $this->assertEquals('list_json_type', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_customer()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');

        $data = $this->run_handler('org.openpsa.invoices', ['list', 'customer', 'all', self::$_person->guid]);
        $this->assertEquals('list_customer_all', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_deliverable()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');

        $salesproject = $this->create_object(org_openpsa_sales_salesproject_dba::class);
        $deliverable = $this->create_object(org_openpsa_sales_salesproject_deliverable_dba::class, ['salesproject' => $salesproject->id]);

        $invoice  = $this->create_object(org_openpsa_invoices_invoice_dba::class);
        $attributes = [
            'invoice' => $invoice->id,
            'deliverable' => $deliverable->id
        ];
        $this->create_object(org_openpsa_invoices_invoice_item_dba::class, $attributes);

        $data = $this->run_handler('org.openpsa.invoices', ['list', 'deliverable', $deliverable->guid]);
        $this->assertEquals('list_deliverable_all', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
