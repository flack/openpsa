<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

require_once('rootfile.php');

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class org_openpsa_sales_salesproject_deliverableTest extends openpsa_testcase
{
    protected static $_salesproject;

    public static function setUpBeforeClass()
    {
        self::_initialize_parent();
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

        self::$_salesproject->refresh();
        $this->assertEquals(self::$_salesproject->value, 250);
        $this->assertEquals(self::$_salesproject->profit, 250);

        $deliverable->plannedUnits = 2;
        $stat = $deliverable->update();
        $this->assertTrue($stat);

        self::$_salesproject->refresh();
        $this->assertEquals(self::$_salesproject->value, 200);
        $this->assertEquals(self::$_salesproject->profit, 200);

        $stat = $deliverable->delete();
        $this->assertTrue($stat);

        self::$_salesproject->calculate_price();
        $this->assertEquals(self::$_salesproject->value, 0);
        $this->assertEquals(self::$_salesproject->profit, 0);

        $_MIDCOM->auth->drop_sudo();
    }

    /**
     * @dataProvider providerCalculate_price
     * @depends testCRUD
     */
    public function testCalculate_price($attributes, $results)
    {
        $deliverable = $this->create_object('org_openpsa_sales_salesproject_deliverable_dba', $attributes);
        foreach ($results as $key => $value)
        {
            $this->assertEquals($value, $deliverable->$key, $key . ' test failed');
        }
    }

    public function providerCalculate_price()
    {
        self::_initialize_parent();

        return array
        (
            array
            (
                array
                (
                    'invoiceByActualUnits' => true,
                    'plannedUnits' => 1,
                    'units' => 1,
                    'pricePerUnit' => 100,
                    'costPerUnit' => 10,
                    'costType' => 'm',
                    'salesproject' => self::$_salesproject->id,
                ),
                array
                (
                    'price' => 100,
                    'cost' => 10,
                ),
            ),
            array
            (
                array
                (
                    'invoiceByActualUnits' => false,
                    'plannedUnits' => 2,
                    'units' => 1,
                    'pricePerUnit' => 100,
                    'costPerUnit' => 10,
                    'costType' => 'm',
                    'salesproject' => self::$_salesproject->id,
                ),
                array
                (
                    'price' => 200,
                    'cost' => 10,
                ),
            ),
            array
            (
                array
                (
                    'invoiceByActualUnits' => true,
                    'plannedUnits' => 0,
                    'units' => 2,
                    'pricePerUnit' => 100,
                    'costPerUnit' => 10,
                    'costType' => '%',
                    'salesproject' => self::$_salesproject->id,
                ),
                array
                (
                    'price' => 200,
                    'cost' => 20,
                ),
            ),
        );
    }

    private function _initialize_parent()
    {
        if (is_null(self::$_salesproject))
        {
            self::$_salesproject = self::create_class_object('org_openpsa_sales_salesproject_dba');
        }
    }

    public function tearDown()
    {
        parent::tearDown();
        self::delete_linked_objects('org_openpsa_sales_salesproject_deliverable_dba', 'salesproject', self::$_salesproject->id);
    }
}
?>