<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\invoices\handler\rest;

use midcom_db_person;
use openpsa_testcase;
use midcom;
use org_openpsa_invoices_invoice_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class invoiceTest extends openpsa_testcase
{
    protected static midcom_db_person $_person;

    public static function setUpBeforeClass() : void
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_get()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');

        $invoice = $this->create_object(org_openpsa_invoices_invoice_dba::class);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['guid' => $invoice->guid];

        $data = $this->run_handler('org.openpsa.invoices', ['rest', 'invoice']);
        $this->assertEquals('invoice_rest', $data['handler_id']);

        $response = $data['__openpsa_testcase_response'];
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($invoice->guid, json_decode($response->getContent())->object->guid);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_get_filter()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');

        $invoice = $this->create_object(org_openpsa_invoices_invoice_dba::class, ['customerContact' => self::$_person->id]);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['guid' => '', 'person_guid' => self::$_person->guid];

        $data = $this->run_handler('org.openpsa.invoices', ['rest', 'invoice']);

        $response = $data['__openpsa_testcase_response'];
        $this->assertEquals(200, $response->getStatusCode());
        $result = json_decode($response->getContent())->data;
        $this->assertCount(1, $result);
        $this->assertEquals($invoice->guid, $result[0]->guid);

        midcom::get()->auth->drop_sudo();
    }
}
