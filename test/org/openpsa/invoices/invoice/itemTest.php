<?php
class itemTest extends PHPUnit_Framework_TestCase
{
    protected static $_invoice;

    public static function setUpBeforeClass()
    {
        require_once('rootfile.php');
        $_MIDCOM->auth->request_sudo('org.openpsa.invoices');
        self::$_invoice = new org_openpsa_invoices_invoice_dba();
        self::$_invoice->create();
        $_MIDCOM->auth->drop_sudo();
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
        self::$_invoice = new org_openpsa_invoices_invoice_dba(self::$_invoice->guid);

        $this->assertEquals(self::$_invoice->sum, 250);

        $stat = $item->delete();
        $this->assertTrue($stat);
        self::$_invoice = new org_openpsa_invoices_invoice_dba(self::$_invoice->guid);
        $this->assertEquals(self::$_invoice->sum, 0);

        $_MIDCOM->auth->drop_sudo();
    }

    public function tearDown()
    {
        $_MIDCOM->auth->request_sudo('org.openpsa.invoices');
        $qb = org_openpsa_invoices_invoice_item_dba::new_query_builder();
        $qb->add_constraint('invoice', '=', self::$_invoice->id);
        $results = $qb->execute();
        foreach ($results as $result)
        {
            $result->delete();
        }
        $_MIDCOM->auth->drop_sudo();
    }

    public static function TearDownAfterClass()
    {
        $_MIDCOM->auth->request_sudo('org.openpsa.invoices');
        self::$_invoice->delete();
        $_MIDCOM->auth->drop_sudo();
    }
}
?>