<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR);
    require_once(OPENPSA_TEST_ROOT . 'rootfile.php');
}

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class org_openpsa_invoices_handler_pdfTest extends openpsa_testcase
{
    protected static $_person;
    protected static $_invoice;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
        self::$_invoice = self::create_class_object('org_openpsa_invoices_invoice_dba');
    }

    public function testHandler_pdf()
    {
        midcom::get('auth')->request_sudo('org.openpsa.invoices');

        $url = $this->run_relocate_handler('org.openpsa.invoices', array('invoice', 'pdf', self::$_invoice->guid));
        $this->assertEquals('invoice/' . self::$_invoice->guid . '/', $url);

        midcom::get('auth')->drop_sudo();
    }
}
?>