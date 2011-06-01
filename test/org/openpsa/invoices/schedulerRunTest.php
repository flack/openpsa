<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR);
    require_once(OPENPSA_TEST_ROOT . 'rootfile.php');
}

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class org_openpsa_invoices_schedulerRunTest extends openpsa_testcase
{
    protected $_product;
    protected $_group;
    protected $_project;
    protected $_task;
    protected $_hour_report;
    protected $_salesproject;
    protected $_deliverable;
    protected $_organization;
    protected $_manager;
    protected $_member;

    public function setUp()
    {
        $this->_organization = $this->create_object('org_openpsa_contacts_group_dba');
        $this->_manager = $this->create_object('midcom_db_person');
        $this->_member = $this->create_object('midcom_db_person');

        $this->_group = $this->create_object('org_openpsa_products_product_group_dba');

        $product_attributes = array
        (
            'productGroup' => $this->_group->id,
            'code' => 'TEST-' . __CLASS__ . time(),
            'delivery' => ORG_OPENPSA_PRODUCTS_DELIVERY_SUBSCRIPTION
        );
        $this->_product = $this->create_object('org_openpsa_products_product_dba', $product_attributes);

        $salesproject_attributes = array
        (
            'owner' => $this->_manager->id,
            'customer' => $this->_organization->id,
        );
        $this->_salesproject = $this->create_object('org_openpsa_sales_salesproject_dba', $salesproject_attributes);

        $member_attributes = array
        (
            'person' => $this->_member->id,
            'objectGuid' => $this->_salesproject->guid,
            'role' => ORG_OPENPSA_OBTYPE_SALESPROJECT_MEMBER
        );
        $this->create_object('org_openpsa_contacts_role_dba', $member_attributes);
        //remember to remove buddylist entry later on

        $deliverable_attributes = array
        (
           'salesproject' => $this->_salesproject->id,
           'product' => $this->_product->id,
           'description' => 'TEST DESCRIPTION',
           'plannedUnits' => 15,
        );
        $this->_deliverable = $this->create_object('org_openpsa_sales_salesproject_deliverable_dba', $deliverable_attributes);

        $this->_project = $this->create_object('org_openpsa_projects_project');

        $task_attributes = array
        (
           'project' => $this->_project->id,
           'agreement' => $this->_deliverable->id,
           'title' => 'TEST TITLE',
        );
        $this->_task = $this->create_object('org_openpsa_projects_task_dba', $task_attributes);

        $this->_hour_report = $this->create_object('org_openpsa_projects_hour_report_dba', array('task' => $this->_task->id));
    }

    private function _apply_input($input)
    {
        foreach ($input as $object => $values)
        {
            foreach ($values as $field => $value)
            {
                $this->$object->$field = $value;
            }
            $this->assertTrue($this->$object->update());
        }
    }

    /**
     * @dataProvider providerRun_cycle
     */
    public function testRun_cycle($params, $input, $result)
    {
        $_MIDCOM->auth->request_sudo('org.openpsa.invoices');
        $this->_apply_input($input);

        $scheduler = new org_openpsa_invoices_scheduler($this->_deliverable);

        $stat = $scheduler->run_cycle($params['cycle_number'], $params['send_invoice']);
        $this->assertTrue($stat);

        foreach ($result as $type => $values)
        {
            if ($type == 'at_entry')
            {
                $this->_verify_at_entry($values);
            }
            else if ($type == 'invoice')
            {
                $this->_verify_invoice($values, $params['cycle_number']);
            }
            else
            {
                $this->$type->refresh();
                foreach ($values as $field => $value)
                {
                    $this->assertEquals($value, $this->$type->$field, 'Difference in ' . $type . ' field ' . $field);
                }
            }
        }

        $_MIDCOM->auth->drop_sudo();
    }

    private function _verify_at_entry($values)
    {
        $mc = new org_openpsa_relatedto_collector($this->_deliverable->guid, 'midcom_services_at_entry_dba');
        $at_entries = $mc->get_related_objects('midcom.services.at');

        $this->assertEquals(1, sizeof($at_entries));
        $at_entry = $at_entries[0];
        $this->register_object($at_entry);
        foreach ($values as $field => $value)
        {
            if ($field == 'start')
            {
                $this->assertEquals(gmstrftime('%x %X', $value), gmstrftime('%x %X', $at_entry->$field), 'Difference in at_entry field ' . $field);
            }
            else
            {
                $this->assertEquals($value, $at_entry->$field, 'Difference in at_entry field ' . $field);
            }
        }
    }

    private function _verify_invoice($values, $cycle_number)
    {
        $mc = org_openpsa_invoices_invoice_item_dba::new_collector('deliverable', $this->_deliverable->id);
        $mc->add_value_property('invoice');
        $mc->set_limit(1);
        $mc->execute();
        $result =  $mc->list_keys();

        if ($values == false)
        {
            $this->assertEquals(0, sizeof($result), 'Invoice was created, which shouldn\'t have happened');
        }
        else
        {
            $this->assertEquals(1, sizeof($result), 'Invoice was not created');
            $invoice = new org_openpsa_invoices_invoice_dba($mc->get_subkey(key($result), 'invoice'));
            $this->register_object($invoice);

            foreach ($values as $field => $value)
            {
                if ($field == 'invoice_items')
                {
                    $this->_verify_invoice_item($invoice, $value);
                    continue;
                }
                $this->assertEquals($value, $invoice->$field, 'Difference in invoice field ' . $field);
            }
            $this->assertEquals($cycle_number, (int) $invoice->parameter('org.openpsa.sales', 'cycle_number'), 'Incorrect cycle number');
        }
    }

    private function _verify_invoice_item($invoice, $items_to_verify)
    {
        $qb = org_openpsa_invoices_invoice_item_dba::new_query_builder();
        $qb->add_constraint('invoice', '=', $invoice->id);
        $items = $qb->execute();
        $this->register_objects($items);

        if ($items_to_verify == false)
        {
            $this->assertEquals(0, sizeof($items), 'Invoice item was created, which shouldn\'t have happened');
        }
        else
        {
            $this->assertEquals(sizeof($items_to_verify), sizeof($items), 'Wrong number of invoice items');

            foreach ($items_to_verify as $values)
            {
                $key = key($values);
                $value = array_shift($values);

                $current_item = null;
                foreach ($items as $i => $item)
                {
                    if ($item->$key == $value)
                    {
                        $current_item = $item;
                        unset($items[$i]);
                        break;
                    }
                }
                $this->assertTrue(is_object($current_item), 'Could not find item with ' . $key . ' == ' . $value);

                foreach ($values as $field => $value)
                {
                    $this->assertEquals($value, $current_item->$field, 'Difference in invoice item field ' . $field);
                }
            }
        }
    }

    private function _generate_unixtime($month, $day, $year)
    {
        do
        {
            $unixtime = gmmktime(0, 0, 0, $month, $day, $year);
        } while (!checkdate($month, $day--, $year));
        return $unixtime;
    }

    public function providerRun_cycle()
    {
        //get the necessary constants
        $_MIDCOM->componentloader->load('org.openpsa.sales');
        $_MIDCOM->componentloader->load('org.openpsa.products');

        $now = time();
        $this_month = gmdate('n', $now);
        $this_day = gmdate('j', $now);
        $this_year = gmdate('Y', $now);

        $midnight_today = gmmktime(0, 0, 0, $this_month, $this_day, $this_year);

        $one_month_future = gmdate('n', $now) + 1;
        $one_month_future_year = gmdate('Y', $now);
        if ($one_month_future > 12)
        {
            $one_month_future = 1;
            $one_month_future_year++;
        }

        $two_month_future = $one_month_future + 1;
        $two_month_future_year = $one_month_future_year;
        if ($two_month_future > 12)
        {
            $two_month_future = 1;
            $two_month_future_year++;
        }

        $one_month_past = gmdate('n', $now) - 1;
        $one_month_past_year = gmdate('Y', $now);
        if ($one_month_past < 1)
        {
            $one_month_past = 12;
            $one_month_past_year--;
        }

        $two_month_past = $one_month_past - 1;
        $two_month_past_year = $one_month_past_year;
        if ($two_month_past < 1)
        {
            $two_month_past = 12;
            $two_month_past_year--;
        }

        $future_one_month = $this->_generate_unixtime($one_month_future, $this_day, $one_month_future_year);
        $future_two_month = $this->_generate_unixtime($two_month_future, $this_day, $two_month_future_year);
        $past_one_month = $this->_generate_unixtime($one_month_past, $this_day, $one_month_past_year);
        $past_two_month = $this->_generate_unixtime($two_month_past, $this_day, $two_month_past_year);

        $beginning_feb = gmmktime(0, 0, 0, 2, 1, 2011);
        $beginning_mar = gmmktime(0, 0, 0, 3, 1, 2011);

        return array
        (

            //SET 0: Deliverable not yet started
            array
            (
                array
                (
                    'cycle_number' => 1,
                    'send_invoice' => true,
                ),
                array
                (
                    '_deliverable' => array
                    (
                        'start' => $future_one_month,
                        'end' => $future_two_month,
                    )
                ),
                array
                (
                    'at_entry' => array
                    (
                        'start' => $future_one_month
                    ),
                    'invoice' => false,
                    '_deliverable' => array
                    (
                        'invoiced' => 0
                    )
                )
            ),

            //SET 1: First deliverable cycle, no invoice yet
            array
            (
                array
                (
                    'cycle_number' => 1,
                    'send_invoice' => true,
                ),
                array
                (
                    '_deliverable' => array
                    (
                        'start' => $past_one_month,
                        'end' => $future_two_month,
                        'unit' => 'm',
                    ),
                ),
                array
                (
                    'at_entry' => array
                    (
                        'start' => $midnight_today
                    ),
                    'invoice' => false,
                    '_deliverable' => array
                    (
                        'invoiced' => 0
                    )
                )
            ),

            //SET 2: First deliverable cycle, invoice by planned units
            array
            (
                array
                (
                    'cycle_number' => 1,
                    'send_invoice' => true,
                ),
                array
                (
                    '_deliverable' => array
                    (
                        'start' => $beginning_feb,
                        'end' => $future_two_month,
                        'invoiceByActualUnits' => false,
                        'plannedUnits' => 17,
                        'pricePerUnit' => 10,
                        'unit' => 'm',
                        'state' => ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_STARTED
                    ),
                    '_product' => array
                    (
                        'delivery' => ORG_OPENPSA_PRODUCTS_DELIVERY_SUBSCRIPTION
                    )
                ),
                array
                (
                    'at_entry' => array
                    (
                        'start' => $beginning_mar
                    ),
                    'invoice' => array
                    (
                        'sum' => 170
                    ),
                    '_deliverable' => array
                    (
                        'invoiced' => 170,
                        'state' => ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_STARTED
                    )
                )
            ),

            //SET 3: second deliverable cycle, invoice by actual units
            array
            (
                array
                (
                    'cycle_number' => 2,
                    'send_invoice' => true,
                ),
                array
                (
                    '_deliverable' => array
                    (
                        'start' => $past_two_month,
                        'end' => $future_two_month,
                        'invoiceByActualUnits' => true,
                        'units' => 17,
                        'pricePerUnit' => 10,
                        'unit' => 'm',
                        'state' => ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_STARTED
                    ),
                    '_product' => array
                    (
                        'orgOpenpsaObtype' => ORG_OPENPSA_PRODUCTS_PRODUCT_TYPE_SERVICE
                    ),
                    '_task' => array
                    (
                        'reportedHours' => 17
                    ),
                    '_hour_report' => array
                    (
                        'hours' => 17,
                        'invoiceable' => true
                    )
                ),
                array
                (
                    '_deliverable' => array
                    (
                        'invoiced' => 170,
                        'state' => ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_STARTED
                    ),
                    '_task' => array
                    (
                        'invoicedHours' => 17
                    ),
                    'invoice' => array
                    (
                        'sum' => 170,
                        'invoice_items' => array
                        (
                            array
                            (
                                'units' => 17,
                                'pricePerUnit' => 10
                            )
                        )
                    )
                )
            ),

            //SET 4: Invoice service by actual units with no invoiceable reports
            array
            (
                'params' => array
                (
                    'cycle_number' => 2,
                    'send_invoice' => true,
                ),
                'input' => array
                (
                    '_deliverable' => array
                    (
                        'title' => 'SET 5',
                        'start' => $past_two_month,
                        'end' => $future_two_month,
                        'invoiceByActualUnits' => true,
                        'units' => 0,
                        'pricePerUnit' => 10,
                        'unit' => 'm',
                        'invoiced' => 170,
                        'state' => ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_STARTED
                    ),
                    '_product' => array
                    (
                        'orgOpenpsaObtype' => ORG_OPENPSA_PRODUCTS_PRODUCT_TYPE_SERVICE
                    ),
                    '_hour_report' => array
                    (
                        'hours' => 17,
                        'invoiceable' => false
                    )
                ),
                'output' => array
                (
                    '_deliverable' => array
                    (
                        'invoiced' => 170,
                        'state' => ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_STARTED
                    ),
                    '_task' => array
                    (
                        'invoicedHours' => 0
                    ),
                    'invoice' => false
                )
            ),

            //SET 5: Invoice goods by actual units
            array
            (
                'params' => array
                (
                    'cycle_number' => 2,
                    'send_invoice' => true,
                ),
                'input' => array
                (
                    '_deliverable' => array
                    (
                        'title' => 'SET 6',
                        'start' => $past_two_month,
                        'end' => $future_two_month,
                        'invoiceByActualUnits' => true,
                        'units' => 10,
                        'pricePerUnit' => 10,
                        'unit' => 'm',
                        'invoiced' => 170,
                        'state' => ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_STARTED
                    ),
                    '_task' => array
                    (
                        'agreement' => 0,
                    ),
                    '_product' => array
                    (
                        'orgOpenpsaObtype' => ORG_OPENPSA_PRODUCTS_PRODUCT_TYPE_GOODS
                    )
                ),
                'output' => array
                (
                    '_deliverable' => array
                    (
                        'invoiced' => 270,
                        'state' => ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_STARTED
                    ),
                    'invoice' => array
                    (
                        'sum' => 100,
                    )
                )
            ),
        );
    }


    /**
     * @depends testRun_cycle
     */
    public function testRun_cycle_multiple()
    {
        $_MIDCOM->auth->request_sudo('org.openpsa.invoices');

        $deliverable_attributes = array
        (
           'salesproject' => $this->_salesproject->id,
           'product' => $this->_product->id,
           'description' => 'TEST DESCRIPTION 2',
           'pricePerUnit' => 10,
           'plannedUnits' => 15,
           'units' => 10,
           'invoiceByActualUnits' => true,
           'state' => ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_STARTED,
           'start' => strtotime('2010-02-02 00:00:00')
        );
        $deliverable2 = $this->create_object('org_openpsa_sales_salesproject_deliverable_dba', $deliverable_attributes);

        $task_attributes = array
        (
           'project' => $this->_project->id,
           'agreement' => $deliverable2->id,
           'title' => 'TEST TITLE 2',
           'reportedHours' => 10
        );
        $task2 = $this->create_object('org_openpsa_projects_task_dba', $task_attributes);

        $this->_product->delivery = ORG_OPENPSA_PRODUCTS_DELIVERY_SUBSCRIPTION;
        $this->_product->update();

        $this->_deliverable->start = strtotime('2010-02-02 00:00:00');
        $this->_deliverable->continuous = true;
        $this->_deliverable->invoiceByActualUnits = false;
        $this->_deliverable->pricePerUnit = 10;
        $this->_deliverable->plannedUnits = 10;
        $this->_deliverable->state = ORG_OPENPSA_SALESPROJECT_DELIVERABLE_STATUS_STARTED;
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
        $this->assertEquals(1, sizeof($results));
        $item1 = $results[0];
        $this->register_object($item1);

        $qb = org_openpsa_invoices_invoice_item_dba::new_query_builder();
        $qb->add_constraint('deliverable', '=', $deliverable2->id);
        $results = $qb->execute();
        $this->assertEquals(1, sizeof($results));
        $item2 = $results[0];
        $this->register_object($item2);

        $this->assertEquals($item1->invoice, $item2->invoice);
        $this->assertEquals($this->_deliverable->id, $item1->deliverable);
        $this->assertEquals($deliverable2->id, $item2->deliverable);

        $invoice = new org_openpsa_invoices_invoice_dba($item2->invoice);
        $this->register_object($invoice);
        $this->assertEquals(200, $invoice->sum);
        $this->assertEquals(100, $deliverable2->invoiced);

        $_MIDCOM->auth->drop_sudo();
    }

}
?>