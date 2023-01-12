<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\sales\salesproject;

use openpsa_testcase;
use org_openpsa_sales_salesproject_dba;
use org_openpsa_products_product_group_dba;
use org_openpsa_products_product_dba;
use org_openpsa_sales_salesproject_deliverable_dba;
use midcom;
use midcom_connection;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class deliverableTest extends openpsa_testcase
{
    protected static $_salesproject;

    public static function setUpBeforeClass() : void
    {
        self::$_salesproject = self::create_class_object(org_openpsa_sales_salesproject_dba::class);
    }

    public function testCRUD()
    {
        midcom::get()->auth->request_sudo('org.openpsa.sales');
        $deliverable = new org_openpsa_sales_salesproject_deliverable_dba();
        $deliverable->salesproject = self::$_salesproject->id;
        $deliverable->plannedUnits = 2.5;
        $deliverable->pricePerUnit = 100;
        $deliverable->_use_rcs = false;

        $stat = $deliverable->create();
        $this->assertTrue($stat, midcom_connection::get_error_string());
        $this->register_object($deliverable);
        $this->assertEquals(250, $deliverable->price);

        $parent = $deliverable->get_parent();
        $this->assertEquals($parent->guid, self::$_salesproject->guid);

        self::$_salesproject->refresh();
        $this->assertEquals(250, self::$_salesproject->value);
        $this->assertEquals(250, self::$_salesproject->profit);

        $deliverable->plannedUnits = 2;
        $stat = $deliverable->update();
        $this->assertTrue($stat);

        self::$_salesproject->refresh();
        $this->assertEquals(200, self::$_salesproject->value);
        $this->assertEquals(200, self::$_salesproject->profit);

        $stat = $deliverable->delete();
        $this->assertTrue($stat);

        self::$_salesproject->calculate_price();
        $this->assertEquals(0, self::$_salesproject->value);
        $this->assertEquals(0, self::$_salesproject->profit);

        midcom::get()->auth->drop_sudo();
    }

    public function testGet_parent()
    {
        $deliverable = $this->create_object(org_openpsa_sales_salesproject_deliverable_dba::class, ['salesproject' => self::$_salesproject->id]);
        $parent = $deliverable->get_parent();
        $this->assertEquals($parent->guid, self::$_salesproject->guid);
    }

    /**
     * @dataProvider providerOrder
     * @depends testCRUD
     */
    public function testOrder($attributes, $retval, $results)
    {
        $productgroup = $this->create_object(org_openpsa_products_product_group_dba::class);
        $attributes['product']['productGroup'] = $productgroup->id;
        $attributes['product']['code'] = __CLASS__ . __FUNCTION__ . time();
        $product = $this->create_object(org_openpsa_products_product_dba::class, $attributes['product']);

        $attributes['deliverable']['product'] = $product->id;
        $attributes['deliverable']['salesproject'] = self::$_salesproject->id;

        $deliverable = $this->create_object(org_openpsa_sales_salesproject_deliverable_dba::class, $attributes['deliverable']);

        midcom::get()->auth->request_sudo('org.openpsa.sales');
        $stat = $deliverable->order();
        midcom::get()->auth->drop_sudo();

        $this->assertEquals($retval, $stat);

        if ($retval === true) {
            $salesproject = self::$_salesproject;
            $salesproject->refresh();

            foreach ($results as $type => $values) {
                foreach ($values as $field => $value) {
                    $this->assertEquals($value, $$type->$field, 'Difference in ' . $type . ' field ' . $field);
                }
            }
        }
    }

    public function providerOrder()
    {
        return [
            0 => [
                'attributes' => [
                    'product' => [
                        'delivery' => org_openpsa_products_product_dba::DELIVERY_SINGLE,
                        'orgOpenpsaObtype' => org_openpsa_products_product_dba::TYPE_GOODS,
                    ],
                    'deliverable' => [
                        'plannedUnits' => 10,
                        'costPerUnit' => 2,
                    ]
                ],
                true,
                'results' => [
                    'deliverable' => [
                        'plannedUnits' => 10,
                        'state' => org_openpsa_sales_salesproject_deliverable_dba::STATE_ORDERED
                    ],
                    'salesproject' => [
                        'state' => org_openpsa_sales_salesproject_dba::STATE_WON
                    ]
                ],
            ],
            1 => [
                'attributes' => [
                    'product' => [],
                    'deliverable' => [
                        'state' => org_openpsa_sales_salesproject_deliverable_dba::STATE_ORDERED
                    ]
                ],
                false,
                'results' => []
            ],
            2 => [
                'attributes' => [
                    'product' => [
                        'delivery' => org_openpsa_products_product_dba::DELIVERY_SINGLE,
                        'orgOpenpsaObtype' => org_openpsa_products_product_dba::TYPE_GOODS,
                    ],
                    'deliverable' => [
                        'plannedUnits' => 10,
                        'costPerUnit' => 2,
                        'invoiceByActualUnits' => true,
                    ]
                ],
                true,
                'results' => [
                    'deliverable' => [
                        'plannedUnits' => 10,
                        'cost' => 0,
                        'units' => 0,
                        'state' => org_openpsa_sales_salesproject_deliverable_dba::STATE_ORDERED
                    ],
                    'salesproject' => [
                        'state' => org_openpsa_sales_salesproject_dba::STATE_WON
                    ]
                ],
            ],
        ];
    }

    /**
     * @depends testCRUD
     */
    public function testDecline()
    {
        $attributes = [
            'salesproject' => self::$_salesproject->id
        ];

        $deliverable = $this->create_object(org_openpsa_sales_salesproject_deliverable_dba::class, $attributes);

        midcom::get()->auth->request_sudo('org.openpsa.sales');
        $stat = $deliverable->decline();

        $this->assertTrue($stat);

        self::$_salesproject->refresh();
        $this->assertEquals(org_openpsa_sales_salesproject_dba::STATE_LOST, self::$_salesproject->state);
        $this->assertEquals(org_openpsa_sales_salesproject_deliverable_dba::STATE_DECLINED, $deliverable->state);

        $deliverable2 = $this->create_object(org_openpsa_sales_salesproject_deliverable_dba::class, $attributes);
        $deliverable3 = $this->create_object(org_openpsa_sales_salesproject_deliverable_dba::class, $attributes);
        self::$_salesproject->state = org_openpsa_sales_salesproject_dba::STATE_ACTIVE;
        self::$_salesproject->update();

        $this->assertTrue($deliverable2->decline());
        $this->assertFalse($deliverable2->decline());

        self::$_salesproject->refresh();
        $this->assertEquals(org_openpsa_sales_salesproject_dba::STATE_ACTIVE, self::$_salesproject->state);

        $this->assertTrue($deliverable3->decline());
        self::$_salesproject->refresh();
        $this->assertEquals(org_openpsa_sales_salesproject_dba::STATE_LOST, self::$_salesproject->state);
        midcom::get()->auth->drop_sudo();
    }

    /**
     * @dataProvider providerCalculate_price
     * @depends testCRUD
     */
    public function testCalculate_price($attributes, $results)
    {
        $deliverable = $this->create_object(org_openpsa_sales_salesproject_deliverable_dba::class, $attributes);
        foreach ($results as $key => $value) {
            $this->assertEquals($value, $deliverable->$key, $key . ' test failed');
        }
    }

    public function providerCalculate_price()
    {
        $salesproject = $this->create_object(org_openpsa_sales_salesproject_dba::class);
        return [
            [
                [
                    'salesproject' => $salesproject->id,
                    'invoiceByActualUnits' => true,
                    'plannedUnits' => 1,
                    'units' => 1,
                    'pricePerUnit' => 100,
                    'costPerUnit' => 10,
                    'costType' => 'm',
                ],
                [
                    'price' => 100,
                    'cost' => 10,
                ],
            ],
            [
                [
                    'salesproject' => $salesproject->id,
                    'invoiceByActualUnits' => false,
                    'plannedUnits' => 2,
                    'units' => 1,
                    'pricePerUnit' => 100,
                    'costPerUnit' => 10,
                    'costType' => 'm',
                ],
                [
                    'price' => 200,
                    'cost' => 20,
                ],
            ],
            [
                [
                    'salesproject' => $salesproject->id,
                    'invoiceByActualUnits' => true,
                    'plannedUnits' => 0,
                    'units' => 2,
                    'pricePerUnit' => 100,
                    'costPerUnit' => 10,
                    'costType' => '%',
                ],
                [
                    'price' => 200,
                    'cost' => 20,
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerGet_cycle_identifier
     */
    public function testGet_cycle_identifier($attributes, $output)
    {
        $deliverable = self::prepare_object(org_openpsa_sales_salesproject_deliverable_dba::class, $attributes);
        $identifier = $deliverable->get_cycle_identifier($deliverable->start);
        $this->assertEquals($identifier, $output);
    }

    public function providerGet_cycle_identifier()
    {
        return [
            [
                [
                    'unit' => 'm',
                    'start' => 1293840000,
                    'end' => 1325376000,
                ],
                '2011-01',
            ],
            [
                [
                    'unit' => 'y',
                    'start' => 1293840000,
                    'end' => 1325376000,
                ],
                '2011',
            ],
            [
                [
                    'unit' => 'q',
                    'start' => 1293840000,
                    'end' => 1325376000,
                ],
                '1Q11',
            ],
            [
                [
                    'unit' => 'hy',
                    'start' => 1293840000,
                    'end' => 1325376000,
                ],
                '1/2011',
            ],
        ];
    }

    public function tearDown() : void
    {
        self::delete_linked_objects('org_openpsa_sales_salesproject_deliverable_dba', 'salesproject', self::$_salesproject->id);
        parent::tearDown();
    }
}
