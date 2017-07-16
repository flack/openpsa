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
class org_openpsa_reports_handler_sales_reportTest extends openpsa_testcase
{
    private static $person;

    public static function setUpBeforeClass()
    {
        self::$person = self::create_user(true);
    }

    public function test_handler_edit_report_guid()
    {
        midcom::get()->auth->request_sudo('org.openpsa.reports');

        $query = $this->create_object('org_openpsa_reports_query_dba');

        $data = $this->run_handler('org.openpsa.reports', ['sales', 'edit', $query->guid]);
        $this->assertEquals('sales_edit_report_guid', $data['handler_id']);

        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }

    public function test_handler_report_guid_file()
    {
        midcom::get()->auth->request_sudo('org.openpsa.reports');

        $query = $this->create_object('org_openpsa_reports_query_dba', [
            'end' => time() + 1000
        ]);
        $query->set_parameter('midcom.helper.datamanager2', 'resource', 'user:' . self::$person->guid);

        $sp = $this->create_object('org_openpsa_sales_salesproject_dba', [
            'owner' => self::$person->id,
            'state' => org_openpsa_sales_salesproject_dba::STATE_WON

        ]);
        $del = $this->create_object('org_openpsa_sales_salesproject_deliverable_dba', [
            'salesproject' => $sp->id,
            'state' => org_openpsa_sales_salesproject_deliverable_dba::STATE_ORDERED
        ]);
        $invoice = $this->create_object('org_openpsa_invoices_invoice_dba', [
            'sent' => time()
        ]);
        $this->create_object('org_openpsa_invoices_invoice_item_dba', [
            'invoice' => $invoice->id,
            'deliverable' => $del->id
        ]);

        $data = $this->run_handler('org.openpsa.reports', ['sales', $query->guid, 'test.csv']);
        $this->assertEquals('sales_report_guid_file', $data['handler_id']);

        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }

    public function test_handler_report_guid()
    {
        midcom::get()->auth->request_sudo('org.openpsa.reports');

        $query = $this->create_object('org_openpsa_reports_query_dba');
        $timestamp = strftime('%Y_%m_%d', $query->metadata->created);

        $url = $this->run_relocate_handler('org.openpsa.reports', ['sales', $query->guid]);

        $this->assertEquals('sales/' . $query->guid . '/' . $timestamp . '_sales.html', $url);

        midcom::get()->auth->drop_sudo();
    }

    public function test_handler_report()
    {
        midcom::get()->auth->request_sudo('org.openpsa.reports');

        $query = $this->create_object('org_openpsa_reports_query_dba');

        $data = $this->run_handler('org.openpsa.reports', ['sales']);
        $this->assertEquals('sales_report', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
