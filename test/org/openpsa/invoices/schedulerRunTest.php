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
                $mc = new org_openpsa_relatedto_collector($this->_deliverable->guid, 'midcom_services_at_entry_dba');
                $at_entries = $mc->get_related_objects('midcom.services.at');

                $this->assertEquals(1, sizeof($at_entries));
                $at_entry = $at_entries[0];
                foreach ($values as $field => $value)
                {
                    $this->assertEquals($value, $at_entry->$field, 'Difference in ' . $type . ' field ' . $field);
                }
                $at_entry->delete();
            }
            else if ($type == 'invoice')
            {
                $mc = new org_openpsa_relatedto_collector($this->_deliverable->guid, 'org_openpsa_invoices_invoice_dba');
                $invoices = $mc->get_related_objects('org.openpsa.invoices');

                if ($values == false)
                {
                    $this->assertEquals(0, sizeof($invoices), 'Invoice was created, which shouldn\'t have happened');
                }
                else
                {
                    $this->assertEquals(1, sizeof($invoices), 'Invoice was created, which shouldn\'t have happened');
                    $invoice = $invoices[0];
                    foreach ($values as $field => $value)
                    {
                        $this->assertEquals($value, $invoice->$field, 'Difference in ' . $type . ' field ' . $field);
                    }

                    $invoice->delete();
                }
            }
            else
            {
                foreach ($values as $field => $value)
                {
                    $this->assertEquals($value, $this->$type->$field, 'Difference in ' . $type . ' field ' . $field);
                }
            }
        }

        $_MIDCOM->auth->drop_sudo();
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
        );
    }
}
?>