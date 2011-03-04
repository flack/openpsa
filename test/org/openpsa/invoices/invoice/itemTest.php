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
class org_openpsa_invoices_invoice_itemTest extends openpsa_testcase
{
    protected static $_invoice;

    public static function setUpBeforeClass()
    {
        self::$_invoice = self::create_class_object('org_openpsa_invoices_invoice_dba');
    }

    public function testCRUD()
    {
        $_MIDCOM->auth->request_sudo('org.openpsa.invoices');
        $item = new org_openpsa_invoices_invoice_item_dba();
        $item->invoice = self::$_invoice->id;
        $item->pricePerUnit = 100;
        $item->units = 2.5;
        $stat = $item->create();
        $this->assertTrue($stat);

        $parent = $item->get_parent();
        $this->assertEquals($parent->guid, self::$_invoice->guid);

        self::$_invoice->refresh();
        $this->assertEquals(self::$_invoice->sum, 250);

        $item->units = 3.5;
        $stat = $item->update();
        $this->assertTrue($stat);

        self::$_invoice->refresh();
        $this->assertEquals(self::$_invoice->sum, 350);

        $stat = $item->delete();
        $this->assertTrue($stat);

        self::$_invoice->refresh();
        $this->assertEquals(self::$_invoice->sum, 0);

        $_MIDCOM->auth->drop_sudo();
    }

    public function tearDown()
    {
        self::delete_linked_objects('org_openpsa_invoices_invoice_item_dba', 'invoice', self::$_invoice->id);
    }
}
?>