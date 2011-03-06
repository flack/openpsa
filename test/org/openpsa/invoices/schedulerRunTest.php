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
            'salesproject' => $this->_salesproject->id,
        );
        $this->create_object('org_openpsa_sales_salesproject_member_dba', $member_attributes);
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
           'up' => $this->_project->id,
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
            $this->$object->update();
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
                $this->_verify_invoice($values);
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
            $this->assertEquals($value, $at_entry->$field, 'Difference in at_entry field ' . $field);
        }
    }

    private function _verify_invoice($values)
    {
        $mc = new org_openpsa_relatedto_collector($this->_deliverable->guid, 'org_openpsa_invoices_invoice_dba');
        $invoices = $mc->get_related_objects('org.openpsa.invoices');

        if ($values == false)
        {
            $this->assertEquals(0, sizeof($invoices), 'Invoice was created, which shouldn\'t have happened');
        }
        else
        {
            $this->assertEquals(1, sizeof($invoices), 'Invoice was not created');
            $invoice = $invoices[0];
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

    public function providerRun_cycle()
    {
        //get the necessary constants
        $_MIDCOM->componentloader->load('org.openpsa.sales');
        $_MIDCOM->componentloader->load('org.openpsa.products');

        $now = time();
        $future_4_weeks = $now + (60 * 60 * 24 * 7 * 4);
        $future_8_weeks = $now + (60 * 60 * 24 * 7 * 4);
        $past_4_weeks = $now - (60 * 60 * 24 * 7 * 4);
        $past_8_weeks = $now - (60 * 60 * 24 * 7 * 4);
        $beginning_feb = gmmktime(0, 0, 0, 2, 1, 2011);
        $beginning_mar = gmmktime(0, 0, 0, 3, 1, 2011);

        return array
        (

            //SET 1: Deliverable not yet started
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
                        'start' => $future_4_weeks,
                        'end' => $future_8_weeks,
                    )
                ),
                array
                (
                    'at_entry' => array
                    (
                        'start' => $future_4_weeks
                    ),
                    'invoice' => false,
                    '_deliverable' => array
                    (
                        'invoiced' => 0
                    )
                )
            ),

            //SET 2: First deliverable cycle, no invoice yet
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
                        'start' => $past_4_weeks,
                        'end' => $future_8_weeks,
                        'unit' => 'm',
                    ),
                ),
                array
                (
                    'at_entry' => array
                    (
                        'start' => gmmktime(0, 0, 0, gmdate('n', $now), gmdate('j', $now), gmdate('Y', $now))
                    ),
                    'invoice' => false,
                    '_deliverable' => array
                    (
                        'invoiced' => 0
                    )
                )
            ),

            //SET 3: First deliverable cycle, invoice by planned units
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
                        'end' => $future_8_weeks,
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

            //SET 4: second deliverable cycle, invoice by actual units
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
                        'start' => $past_8_weeks,
                        'end' => $future_8_weeks,
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

            //SET 5: Invoice service by actual units with no invoiceable reports
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
                        'start' => $past_8_weeks,
                        'end' => $future_8_weeks,
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

            //SET 6: Invoice goods by actual units
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
                        'start' => $past_8_weeks,
                        'end' => $future_8_weeks,
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
}
?>