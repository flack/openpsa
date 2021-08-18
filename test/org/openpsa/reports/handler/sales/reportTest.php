<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\reports\handler\sales;

use openpsa_testcase;
use midcom;
use org_openpsa_reports_query_dba;
use org_openpsa_sales_salesproject_dba;
use org_openpsa_sales_salesproject_deliverable_dba;
use org_openpsa_invoices_invoice_dba;
use org_openpsa_invoices_invoice_item_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class reportTest extends openpsa_testcase
{
    private static $person;

    public static function setUpBeforeClass() : void
    {
        self::$person = self::create_user(true);
    }

    public function test_handler_edit_report_guid()
    {
        midcom::get()->auth->request_sudo('org.openpsa.reports');

        $query = $this->create_object(org_openpsa_reports_query_dba::class);

        $data = $this->run_handler('org.openpsa.reports', ['sales', 'edit', $query->guid]);
        $this->assertEquals('sales_edit_report_guid', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function test_handler_report_guid_file()
    {
        midcom::get()->auth->request_sudo('org.openpsa.reports');

        $query = $this->create_object(org_openpsa_reports_query_dba::class, [
            'end' => time() + 1000
        ]);
        $query->set_parameter('midcom.helper.datamanager2', 'resource', 'all');

        $sp = $this->create_object(org_openpsa_sales_salesproject_dba::class, [
            'owner' => self::$person->id,
            'customerContact' => self::$person->id,
            'state' => org_openpsa_sales_salesproject_dba::STATE_WON
        ]);

        $del = $this->create_object(org_openpsa_sales_salesproject_deliverable_dba::class, [
            'salesproject' => $sp->id,
            'state' => org_openpsa_sales_salesproject_deliverable_dba::STATE_ORDERED
        ]);
        $invoice = $this->create_object(org_openpsa_invoices_invoice_dba::class, [
            'sent' => time()
        ]);
        $this->create_object(org_openpsa_invoices_invoice_item_dba::class, [
            'invoice' => $invoice->id,
            'deliverable' => $del->id
        ]);

        $data = $this->run_handler('org.openpsa.reports', ['sales', $query->guid, 'test.csv']);
        $this->assertEquals('sales_report_guid_file', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function test_handler_report_guid()
    {
        midcom::get()->auth->request_sudo('org.openpsa.reports');

        $query = $this->create_object(org_openpsa_reports_query_dba::class);
        $timestamp = strftime('%Y_%m_%d', $query->metadata->created);

        $url = $this->run_relocate_handler('org.openpsa.reports', ['sales', $query->guid]);

        $this->assertEquals('sales/' . $query->guid . '/' . $timestamp . '_sales.html', $url);

        midcom::get()->auth->drop_sudo();
    }

    public function test_handler_report()
    {
        midcom::get()->auth->request_sudo('org.openpsa.reports');

        $this->create_object(org_openpsa_reports_query_dba::class);

        $data = $this->run_handler('org.openpsa.reports', ['sales']);
        $this->assertEquals('sales_report', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
