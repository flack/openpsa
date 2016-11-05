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
class org_openpsa_invoices_handler_invoice_pdfTest extends openpsa_testcase
{
    protected static $_person;
    protected static $_invoice;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
        self::$_invoice = self::create_class_object('org_openpsa_invoices_invoice_dba');
    }

    /**
     * @expectedException midcom_error
     * @todo: Once we have a way to inject config values, we should add a mock object here
     */
    public function testHandler_pdf()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');

        $topic = $this->create_object('midcom_db_topic', array('component' => 'org.openpsa.invoices'));
        $topic->set_parameter('org.openpsa.invoices', 'invoice_pdfbuilder_class', 'nonexistent');

        $url = $this->run_relocate_handler($topic, array('invoice', 'pdf', self::$_invoice->guid));
        $this->assertEquals('invoice/' . self::$_invoice->guid . '/', $url);

        midcom::get()->auth->drop_sudo();
    }
}
