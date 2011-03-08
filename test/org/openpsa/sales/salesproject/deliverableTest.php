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
class org_openpsa_sales_salesproject_deliverableTest extends openpsa_testcase
{
    protected $_salesproject;

    public function setUp()
    {
        $this->_salesproject = $this->create_object('org_openpsa_sales_salesproject_dba');
    }

    public function testCRUD()
    {
        $_MIDCOM->auth->request_sudo('org.openpsa.sales');
        $deliverable = new org_openpsa_sales_salesproject_deliverable_dba();
        $deliverable->salesproject = $this->_salesproject->id;
        $deliverable->plannedUnits = 2.5;
        $deliverable->pricePerUnit = 100;
        $stat = $deliverable->create();
        $this->assertTrue($stat, midcom_connection::get_error_string());
        $this->register_object($deliverable);
        $this->assertEquals($deliverable->price, 250);

        $parent = $deliverable->get_parent();
        $this->assertEquals($parent->guid, $this->_salesproject->guid);

        $this->_salesproject->refresh();
        $this->assertEquals($this->_salesproject->value, 250);
        $this->assertEquals($this->_salesproject->profit, 250);

        $deliverable->plannedUnits = 2;
        $stat = $deliverable->update();
        $this->assertTrue($stat);

        $this->_salesproject->refresh();
        $this->assertEquals($this->_salesproject->value, 200);
        $this->assertEquals($this->_salesproject->profit, 200);

        $stat = $deliverable->delete();
        $this->assertTrue($stat);

        $this->_salesproject->calculate_price();
        $this->assertEquals($this->_salesproject->value, 0);
        $this->assertEquals($this->_salesproject->profit, 0);

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
        $this->_salesproject = $this->create_object('org_openpsa_sales_salesproject_dba');

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
                    'salesproject' => $this->_salesproject->id,
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
                    'salesproject' => $this->_salesproject->id,
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
                    'salesproject' => $this->_salesproject->id,
                ),
                array
                (
                    'price' => 200,
                    'cost' => 20,
                ),
            ),
        );
    }

    public function tearDown()
    {
        self::delete_linked_objects('org_openpsa_sales_salesproject_deliverable_dba', 'salesproject', $this->_salesproject->id);
        parent::tearDown();
    }
}
?>