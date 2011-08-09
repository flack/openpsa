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
class org_openpsa_invoices_handler_actionTest extends openpsa_testcase
{
    protected static $_person;
    protected static $_invoice;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
        self::$_invoice = self::create_class_object('org_openpsa_invoices_invoice_dba');
    }

    public function testHandler_process_mark_sent()
    {
        midcom::get('auth')->request_sudo('org.openpsa.invoices');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = array
        (
            'action' => 'mark_sent',
            'id' => self::$_invoice->id,
            'relocate' => true
        );
        $url = $this->run_relocate_handler('org.openpsa.invoices', array('invoice', 'process'));
        $this->assertEquals('', $url);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_process_mark_paid()
    {
        midcom::get('auth')->request_sudo('org.openpsa.invoices');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = array
        (
            'action' => 'mark_paid',
            'id' => self::$_invoice->id,
            'relocate' => true
        );
        $url = $this->run_relocate_handler('org.openpsa.invoices', array('invoice', 'process'));
        $this->assertEquals('', $url);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_items()
    {
        midcom::get('auth')->request_sudo('org.openpsa.invoices');

        $data = $this->run_handler('org.openpsa.invoices', array('invoice', 'items', self::$_invoice->guid));
        $this->assertEquals('invoice_items', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_recalculation()
    {
        midcom::get('auth')->request_sudo('org.openpsa.invoices');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $url = $this->run_relocate_handler('org.openpsa.invoices', array('invoice', 'recalculation', self::$_invoice->guid));
        $this->assertEquals('invoice/items/' . self::$_invoice->guid . '/', $url);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_itemedit()
    {
        midcom::get('auth')->request_sudo('org.openpsa.invoices');
        $item = $this->create_object('org_openpsa_invoices_invoice_item_dba', array('invoice' => self::$_invoice->id));

        $_POST = array
        (
            'oper' => 'edit',
            'id' => $item->id,
            'description' => 'TEST DESCRIPTION',
            'price' => 20,
            'quantity' => 10
        );

        $data = $this->run_handler('org.openpsa.invoices', array('invoice', 'itemedit', self::$_invoice->guid));
        $this->assertEquals('invoice_item_edit', $data['handler_id']);

        $item->refresh();
        $this->assertEquals(20, $item->pricePerUnit);
        $this->assertEquals(10, $item->units);
        $this->assertEquals('TEST DESCRIPTION', $item->description);

        midcom::get('auth')->drop_sudo();
    }
}
?>