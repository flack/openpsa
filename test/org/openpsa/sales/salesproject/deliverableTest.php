<?php
require_once('rootfile.php');

class deliverableTest extends openpsa_testcase
{
    protected static $_salesproject;

    public static function setUpBeforeClass()
    {
        self::$_salesproject = self::create_class_object('org_openpsa_sales_salesproject_dba');
    }

    public function testCRUD()
    {
        $_MIDCOM->auth->request_sudo('org.openpsa.sales');
        $deliverable = new org_openpsa_sales_salesproject_deliverable_dba();
        $deliverable->salesproject = self::$_salesproject->id;
        $deliverable->plannedUnits = 2.5;
        $deliverable->pricePerUnit = 100;
        $stat = $deliverable->create();
        $this->assertTrue($stat);

        $this->assertEquals($deliverable->price, 250);

        $parent = $deliverable->get_parent();
        $this->assertEquals($parent->guid, self::$_salesproject->guid);

        self::$_salesproject->calculate_price();
        $this->assertEquals(self::$_salesproject->value, 250);
        $this->assertEquals(self::$_salesproject->profit, 250);

        $deliverable->plannedUnits = 2;
        $stat = $deliverable->update();
        $this->assertTrue($stat);

        self::$_salesproject->calculate_price();
        $this->assertEquals(self::$_salesproject->value, 200);
        $this->assertEquals(self::$_salesproject->profit, 200);

        $stat = $deliverable->delete();
        $this->assertTrue($stat);

        self::$_salesproject->calculate_price();
        $this->assertEquals(self::$_salesproject->value, 0);
        $this->assertEquals(self::$_salesproject->profit, 0);

        $_MIDCOM->auth->drop_sudo();
    }

    public function tearDown()
    {
        self::delete_linked_objects('org_openpsa_sales_salesproject_deliverable_dba', 'salesproject', self::$_salesproject->id);
    }
}
?>