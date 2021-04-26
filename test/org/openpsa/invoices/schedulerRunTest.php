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
class org_openpsa_invoices_schedulerRunTest extends openpsa_testcase
{
    protected static $organization;
    protected static $manager;
    protected static $member;
    protected static $group;

    protected $_product;
    protected $_project;
    protected $_task;
    protected $_hour_report;
    protected $_salesproject;
    protected $_deliverable;

    public static function setUpBeforeClass() : void
    {
        self::$organization = self::create_class_object(org_openpsa_contacts_group_dba::class);
        self::$manager = self::create_class_object(midcom_db_person::class);
        self::$member = self::create_class_object(midcom_db_person::class);
        self::$group = self::create_class_object(org_openpsa_products_product_group_dba::class);
    }

    public function setUp() : void
    {
        $product_attributes = [
            'productGroup' => self::$group->id,
            'code' => 'TEST-' . __CLASS__ . time(),
            'delivery' => org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION
        ];
        $this->_product = $this->create_object(org_openpsa_products_product_dba::class, $product_attributes);

        $salesproject_attributes = [
            'owner' => self::$manager->id,
            'customer' => self::$organization->id,
        ];
        $this->_salesproject = $this->create_object(org_openpsa_sales_salesproject_dba::class, $salesproject_attributes);

        $member_attributes = [
            'person' => self::$member->id,
            'objectGuid' => $this->_salesproject->guid,
            'role' => org_openpsa_sales_salesproject_dba::ROLE_MEMBER
        ];
        $this->create_object(org_openpsa_contacts_role_dba::class, $member_attributes);

        $deliverable_attributes = [
            'salesproject' => $this->_salesproject->id,
            'product' => $this->_product->id,
            'description' => 'TEST DESCRIPTION',
            'plannedUnits' => 15,
            'orgOpenpsaObtype' => org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION,
            'unit' => 'm'
        ];
        $this->_deliverable = $this->create_object(org_openpsa_sales_salesproject_deliverable_dba::class, $deliverable_attributes);

        $this->_project = $this->_salesproject->get_project();

        $task_attributes = [
           'project' => $this->_project->id,
           'agreement' => $this->_deliverable->id,
           'title' => 'TEST TITLE',
        ];
        $this->_task = $this->create_object(org_openpsa_projects_task_dba::class, $task_attributes);

        $this->_hour_report = $this->create_object(org_openpsa_expenses_hour_report_dba::class, ['task' => $this->_task->id]);
    }

    private function _apply_input(array $input)
    {
        foreach ($input as $object => $values) {
            foreach ($values as $field => $value) {
                if (   $object == '_deliverable'
                    && $field == 'invoiced') {
                    //since invoiced value is auto-generated, we need to create an item for that
                    $this->_create_invoice_item($value);
                } else {
                    $this->$object->$field = $value;
                }
            }
            $this->assertTrue($this->$object->update());
        }
    }

    private function _create_invoice_item($value)
    {
        if ($value == 0) {
            return;
        }
        $this->assertTrue($this->_deliverable->update());

        $invoice_data = [
            'sent' => time()
        ];
        $invoice = $this->create_object(org_openpsa_invoices_invoice_dba::class, $invoice_data);
        $item_data = [
            'deliverable' => $this->_deliverable->id,
            'invoice' => $invoice->id,
            'units' => 1,
            'pricePerUnit' => $value
        ];

        $this->create_object(org_openpsa_invoices_invoice_item_dba::class, $item_data);
        $this->_deliverable->refresh();
    }

    /**
     * @dataProvider providerRun_cycle
     */
    public function testRun_cycle($params, $input, $result)
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');
        $this->_apply_input($input);

        $scheduler = new org_openpsa_invoices_scheduler($this->_deliverable);

        $stat = $scheduler->run_cycle($params['cycle_number'], $params['send_invoice']);
        $this->assertTrue($stat);

        foreach ($result as $type => $values) {
            switch ($type) {
                case 'at_entry':
                    $this->_verify_at_entry($values);
                    break;
                case 'invoice':
                    $this->_verify_invoice($values, $params['cycle_number']);
                    break;
                case 'new_task':
                    $this->_verify_new_task();
                    break;
                default:
                    $this->$type->refresh();
                    foreach ($values as $field => $value) {
                        $this->assertEquals($value, $this->$type->$field, 'Difference in ' . $type . ' field ' . $field);
                    }
            }
        }

        midcom::get()->auth->drop_sudo();
    }

    private function _verify_new_task()
    {
        $qb = org_openpsa_projects_task_dba::new_query_builder();
        $qb->add_constraint('guid', '<>', $this->_task->guid);
        $qb->add_constraint('project', '=', $this->_project->id);
        $results = $qb->execute();
        $this->register_objects($results);
        $this->assertCount(1, $results);
        $new_task = $results[0];
        $this->assertEquals($this->_deliverable->id, $new_task->agreement);
        $this->assertEquals($this->_salesproject->customer, $new_task->customer);
        $this->assertEquals($this->_task->manager, $new_task->manager);
    }

    private function _verify_at_entry($values)
    {
        $mc = new org_openpsa_relatedto_collector($this->_deliverable->guid, 'midcom_services_at_entry_dba');
        $mc->add_constraint('fromComponent', '=', 'midcom.services.at');
        $at_entries = $mc->get_related_objects();
        $this->register_objects($at_entries);

        $this->assertCount(1, $at_entries);
        $at_entry = $at_entries[0];

        foreach ($values as $field => $value) {
            if ($field == 'start') {
                $this->assertEquals(gmstrftime('%x %X', $value), gmstrftime('%x %X', $at_entry->$field), 'Difference in at_entry field ' . $field);
            } else {
                $this->assertEquals($value, $at_entry->$field, 'Difference in at_entry field ' . $field);
            }
        }
    }

    private function _verify_invoice($values, $cycle_number)
    {
        $mc = org_openpsa_invoices_invoice_item_dba::new_collector('deliverable', $this->_deliverable->id);
        $mc->add_constraint('invoice.sent', '=', 0);
        $mc->add_value_property('invoice');
        $mc->set_limit(1);
        $mc->execute();
        $result =  $mc->list_keys();

        if (!$values) {
            $this->assertCount(0, $result, 'Invoice was created, which shouldn\'t have happened');
        } else {
            $this->assertCount(1, $result, 'Invoice was not created');
            $invoice = new org_openpsa_invoices_invoice_dba($mc->get_subkey(key($result), 'invoice'));
            $this->register_object($invoice);

            foreach ($values as $field => $value) {
                if ($field == 'invoice_items') {
                    $this->_verify_invoice_item($invoice, $value);
                    continue;
                }
                $this->assertEquals($value, $invoice->$field, 'Difference in invoice field ' . $field);
            }
            $this->assertEquals($cycle_number, (int) $invoice->get_parameter('org.openpsa.sales', 'cycle_number'), 'Incorrect cycle number');
        }
    }

    private function _verify_invoice_item($invoice, $items_to_verify)
    {
        $qb = org_openpsa_invoices_invoice_item_dba::new_query_builder();
        $qb->add_constraint('invoice', '=', $invoice->id);
        $items = $qb->execute();
        $this->register_objects($items);

        if (!$items_to_verify) {
            $this->assertCount(0, $items, 'Invoice item was created, which shouldn\'t have happened');
        } else {
            $this->assertCount(count($items_to_verify), $items, 'Wrong number of invoice items');

            foreach ($items_to_verify as $values) {
                $key = key($values);
                $value = array_shift($values);

                $current_item = null;
                foreach ($items as $i => $item) {
                    if ($item->$key == $value) {
                        $current_item = $item;
                        unset($items[$i]);
                        break;
                    }
                }
                $this->assertIsObject($current_item, 'Could not find item with ' . $key . ' == ' . $value);

                foreach ($values as $field => $value) {
                    $this->assertEquals($value, $current_item->$field, 'Difference in invoice item field ' . $field);
                }
            }
        }
    }

    private function _generate_unixtime($month, $day, $year)
    {
        do {
            $unixtime = gmmktime(0, 0, 0, $month, $day, $year);
        } while (!checkdate($month, $day--, $year));
        return $unixtime;
    }

    public function providerRun_cycle()
    {
        $now = time();
        $this_month = gmdate('n', $now);
        $this_day = gmdate('j', $now);
        $this_year = gmdate('Y', $now);

        $midnight_today = gmmktime(0, 0, 0, $this_month, $this_day, $this_year);

        $one_month_future = gmdate('n', $now) + 1;
        $one_month_future_year = gmdate('Y', $now);
        if ($one_month_future > 12) {
            $one_month_future = 1;
            $one_month_future_year++;
        }

        $two_month_future = $one_month_future + 1;
        $two_month_future_year = $one_month_future_year;
        if ($two_month_future > 12) {
            $two_month_future = 1;
            $two_month_future_year++;
        }

        $one_month_past = gmdate('n', $now) - 1;
        $one_month_past_year = gmdate('Y', $now);
        if ($one_month_past < 1) {
            $one_month_past = 12;
            $one_month_past_year--;
        }

        $two_month_past = $one_month_past - 1;
        $two_month_past_year = $one_month_past_year;
        if ($two_month_past < 1) {
            $two_month_past = 12;
            $two_month_past_year--;
        }

        $past_two_month = $this->_generate_unixtime($two_month_past, $this_day, $two_month_past_year);
        $past_one_month = $this->_generate_unixtime($one_month_past, $this_day, $one_month_past_year);
        $future_one_month = $this->_generate_unixtime($one_month_future, $this_day, $one_month_future_year);
        $future_two_month = $this->_generate_unixtime($two_month_future, $this_day, $two_month_future_year);

        //If one of our dates is at the end of the month, align the others to be at the end of the month as well
        if (    gmdate('t', $past_two_month) == gmdate('j', $past_two_month)
             || gmdate('t', $past_one_month) == gmdate('j', $past_one_month)) {
            $past_one_month += (gmdate('t', $past_one_month) - gmdate('j', $past_one_month)) * 24 * 60 * 60;
            $midnight_today += (gmdate('t', $midnight_today) - gmdate('j', $midnight_today)) * 24 * 60 * 60;
            $future_one_month += (gmdate('t', $future_one_month) - gmdate('j', $future_one_month)) * 24 * 60 * 60;
            $future_two_month += (gmdate('t', $future_two_month) - gmdate('j', $future_two_month)) * 24 * 60 * 60;
        }

        $beginning_feb = gmmktime(0, 0, 0, 2, 1, 2011);
        $beginning_mar = gmmktime(0, 0, 0, 3, 1, 2011);

        //@todo These two aren't properly cleaned up after the test
        $customer = $this->create_object(org_openpsa_contacts_group_dba::class);
        $customer_contact = $this->create_object(org_openpsa_contacts_person_dba::class);

        return [
            //SET 0: Deliverable not yet started
            [
                [
                    'cycle_number' => 1,
                    'send_invoice' => true,
                ],
                [
                    '_deliverable' => [
                        'start' => $future_one_month,
                        'end' => $future_two_month,
                        'unit' => 'm',
                    ]
                ],
                [
                    'at_entry' => [
                        'start' => $future_one_month
                    ],
                    'invoice' => false,
                    '_deliverable' => [
                        'invoiced' => 0
                    ]
                ]
            ],

            //SET 1: First deliverable cycle, no invoice yet
            [
                [
                    'cycle_number' => 1,
                    'send_invoice' => true,
                ],
                [
                    '_deliverable' => [
                        'start' => $past_one_month,
                        'end' => $future_two_month,
                        'unit' => 'm',
                    ],
                ],
                [
                    'at_entry' => [
                        'start' => $midnight_today
                    ],
                    'invoice' => false,
                    '_deliverable' => [
                        'invoiced' => 0
                    ]
                ]
            ],

            //SET 2: First deliverable cycle, invoice by planned units, customer is set
            [
                [
                    'cycle_number' => 1,
                    'send_invoice' => true,
                ],
                [
                    '_salesproject' => [
                        'customer' => $customer->id,
                    ],
                    '_deliverable' => [
                        'start' => $beginning_feb,
                        'end' => $future_two_month,
                        'invoiceByActualUnits' => false,
                        'plannedUnits' => 12,
                        'pricePerUnit' => 10,
                        'unit' => 'm',
                        'state' => org_openpsa_sales_salesproject_deliverable_dba::STATE_STARTED
                    ],
                    '_product' => [
                        'delivery' => org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION
                    ]
                ],
                [
                    'at_entry' => [
                        'start' => $beginning_mar
                    ],
                    'invoice' => [
                        'sum' => 120,
                        'customer' => $customer->id
                    ],
                    '_deliverable' => [
                        'invoiced' => 120,
                        'state' => org_openpsa_sales_salesproject_deliverable_dba::STATE_STARTED
                    ]
                ]
            ],

            //SET 3: second deliverable cycle, invoice by actual units, customerContact is set
            [
                [
                    'cycle_number' => 2,
                    'send_invoice' => true,
                ],
                [
                    '_salesproject' => [
                        'customerContact' => $customer_contact->id
                    ],
                    '_deliverable' => [
                        'start' => $past_two_month,
                        'end' => $future_two_month,
                        'invoiceByActualUnits' => true,
                        'units' => 13,
                        'pricePerUnit' => 10,
                        'unit' => 'm',
                        'state' => org_openpsa_sales_salesproject_deliverable_dba::STATE_STARTED
                    ],
                    '_product' => [
                        'orgOpenpsaObtype' => org_openpsa_products_product_dba::TYPE_SERVICE
                    ],
                    '_task' => [
                        'reportedHours' => 13
                    ],
                    '_hour_report' => [
                        'hours' => 13,
                        'invoiceable' => true
                    ]
                ],
                [
                    '_deliverable' => [
                        'invoiced' => 130,
                        'state' => org_openpsa_sales_salesproject_deliverable_dba::STATE_STARTED
                    ],
                    '_task' => [
                        'invoicedHours' => 13
                    ],
                    'invoice' => [
                        'sum' => 130,
                        'invoice_items' => [
                            [
                                'units' => 13,
                                'pricePerUnit' => 10
                            ]
                        ],
                        'customerContact' => $customer_contact->id
                    ],
                    'new_task' => true,
                ]
            ],

            //SET 4: Invoice service by actual units with no invoiceable reports
            [
                'params' => [
                    'cycle_number' => 2,
                    'send_invoice' => true,
                ],
                'input' => [
                    '_deliverable' => [
                        'title' => 'SET 4',
                        'start' => $past_two_month,
                        'end' => $future_two_month,
                        'invoiceByActualUnits' => true,
                        'units' => 0,
                        'pricePerUnit' => 10,
                        'unit' => 'm',
                        'invoiced' => 140,
                        'state' => org_openpsa_sales_salesproject_deliverable_dba::STATE_STARTED
                    ],
                    '_product' => [
                        'orgOpenpsaObtype' => org_openpsa_products_product_dba::TYPE_SERVICE
                    ],
                    '_hour_report' => [
                        'hours' => 14,
                        'invoiceable' => false
                    ]
                ],
                'output' => [
                    '_deliverable' => [
                        'invoiced' => 140,
                        'state' => org_openpsa_sales_salesproject_deliverable_dba::STATE_STARTED
                    ],
                    '_task' => [
                        'invoicedHours' => 0
                    ],
                    'invoice' => false,
                    'new_task' => true,
                ]
            ],

            //SET 5: Invoice goods by actual units
            [
                'params' => [
                    'cycle_number' => 2,
                    'send_invoice' => true,
                ],
                'input' => [
                    '_deliverable' => [
                        'title' => 'SET 5',
                        'start' => $past_two_month,
                        'end' => $future_two_month,
                        'invoiceByActualUnits' => true,
                        'units' => 10,
                        'pricePerUnit' => 10,
                        'unit' => 'm',
                        'invoiced' => 180,
                        'state' => org_openpsa_sales_salesproject_deliverable_dba::STATE_STARTED
                    ],
                    '_task' => [
                        'agreement' => 0,
                    ],
                    '_product' => [
                        'orgOpenpsaObtype' => org_openpsa_products_product_dba::TYPE_GOODS
                    ]
                ],
                'output' => [
                    '_deliverable' => [
                        'invoiced' => 280,
                        'state' => org_openpsa_sales_salesproject_deliverable_dba::STATE_STARTED
                    ],
                    'invoice' => [
                        'sum' => 100,
                    ]
                ]
            ],
        ];
    }


    /**
     * @depends testRun_cycle
     */
    public function testRun_cycle_multiple()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');

        $deliverable_attributes = [
           'salesproject' => $this->_salesproject->id,
           'product' => $this->_product->id,
           'description' => 'TEST DESCRIPTION 2',
           'pricePerUnit' => 10,
           'plannedUnits' => 15,
           'units' => 10,
           'unit' => 'm',
           'invoiceByActualUnits' => true,
           'state' => org_openpsa_sales_salesproject_deliverable_dba::STATE_STARTED,
           'start' => strtotime('2010-02-02 00:00:00')
        ];
        $deliverable2 = $this->create_object(org_openpsa_sales_salesproject_deliverable_dba::class, $deliverable_attributes);

        $task_attributes = [
           'project' => $this->_project->id,
           'agreement' => $deliverable2->id,
           'title' => 'TEST TITLE 2',
           'reportedHours' => 10
        ];
        $this->create_object(org_openpsa_projects_task_dba::class, $task_attributes);

        $this->_product->delivery = org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION;
        $this->_product->update();

        $this->_deliverable->start = strtotime('2010-02-02 00:00:00');
        $this->_deliverable->continuous = true;
        $this->_deliverable->invoiceByActualUnits = false;
        $this->_deliverable->pricePerUnit = 10;
        $this->_deliverable->plannedUnits = 10;
        $this->_deliverable->state = org_openpsa_sales_salesproject_deliverable_dba::STATE_STARTED;
        $this->_deliverable->update();

        $scheduler = new org_openpsa_invoices_scheduler($this->_deliverable);
        $stat = $scheduler->run_cycle(1, true);
        $this->assertTrue($stat);

        $scheduler = new org_openpsa_invoices_scheduler($deliverable2);
        $stat = $scheduler->run_cycle(1, true);
        $this->assertTrue($stat);

        $qb = org_openpsa_invoices_invoice_item_dba::new_query_builder();
        $qb->add_constraint('deliverable', '=', $this->_deliverable->id);
        $results = $qb->execute();
        $this->assertCount(1, $results);
        $item1 = $results[0];
        $this->register_object($item1);

        $qb = org_openpsa_invoices_invoice_item_dba::new_query_builder();
        $qb->add_constraint('deliverable', '=', $deliverable2->id);
        $results = $qb->execute();
        $this->assertCount(1, $results);
        $item2 = $results[0];
        $this->register_object($item2);

        $this->assertEquals($item1->invoice, $item2->invoice);
        $this->assertEquals($this->_deliverable->id, $item1->deliverable);
        $this->assertEquals($deliverable2->id, $item2->deliverable);

        $invoice = new org_openpsa_invoices_invoice_dba($item2->invoice);
        $this->register_object($invoice);
        $this->assertEquals(200, $invoice->sum);
        $this->assertEquals(100, $deliverable2->invoiced);

        midcom::get()->auth->drop_sudo();
    }
}
