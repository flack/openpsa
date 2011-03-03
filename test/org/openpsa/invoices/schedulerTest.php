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
class org_openpsa_invoices_schedulerTest extends openpsa_testcase
{
    protected $_salesproject;

    /**
     * @dataProvider providerCalculate_cycle_next
     */
    public function testCalculate_cycle_next($unit, $start, $result)
    {
        $deliverable = new org_openpsa_sales_salesproject_deliverable_dba();
        $deliverable->unit = $unit;

        $scheduler = new org_openpsa_invoices_scheduler($deliverable);
        $next_cycle = $scheduler->calculate_cycle_next($start);

        $this->assertEquals($result, $next_cycle, 'Wrong value for unit ' . $unit);
    }

    public function providerCalculate_cycle_next()
    {
        return array
        (
            array
            (
                'd',
                1297468800,
                1297555200,
            ),
            array
            (
                'm',
                1297468800,
                1299888000,
            ),
            array
            (
                'm',
                1296518400,
                1298937600,
            ),
            array
            (
               'q',
                1297468800,
                1305158400,
            ),
            array
            (
                'y',
                1297468800,
                1329004800,
            ),
            array
            (
                'x',
                1297468800,
                false,
            ),
        );
    }

    /**
     * @dataProvider providerCalculate_cycles
     * @depends testCalculate_cycle_next
     */
    public function testCalculate_cycles($attributes, $months, $result)
    {
        $deliverable = self::prepare_object('org_openpsa_sales_salesproject_deliverable_dba', $attributes);

        $scheduler = new org_openpsa_invoices_scheduler($deliverable);
        $cycles = $scheduler->calculate_cycles($months);

        $this->assertEquals($result, $cycles, 'Wrong value for unit ' . $deliverable->unit);
    }

    public function providerCalculate_cycles()
    {
        return array
        (
            array
            (
                array
                (
                    'unit' => 'd',
                    'start' => 1293840000,
                    'end' => 1325376000,
                ),
                null,
                365,
            ),
            array
            (
                array
                (
                    'unit' => 'm',
                    'start' => 1293840000,
                    'end' => 1325376000,
                ),
                null,
                12,
            ),
            array
            (
                array
                (
                    'unit' => 'y',
                    'start' => 1293840000,
                    'end' => 1325376000,
                ),
                null,
                1,
            ),
            array
            (
                array
                (
                    'unit' => 'd',
                    'start' => 1293840000,
                    'end' => 1325376000,
                ),
                1,
                31,
            ),
        );
    }

    public function testCreate_task()
    {
        $organization = $this->create_object('org_openpsa_contacts_group_dba');
        $manager = $this->create_object('midcom_db_person');
        $member = $this->create_object('midcom_db_person');

        $group = $this->create_object('org_openpsa_products_product_group_dba');

        $product_attributes = array
        (
            'productGroup' => $group->id,
            'code' => 'TEST-' . __CLASS__ . time(),
        );
        $product = $this->create_object('org_openpsa_products_product_dba', $product_attributes);

        $salesproject_attributes = array
        (
            'owner' => $manager->id,
            'customer' => $organization->id,
        );
        $salesproject = $this->create_object('org_openpsa_sales_salesproject_dba', $salesproject_attributes);

        $member_attributes = array
        (
            'person' => $member->id,
            'salesproject' => $salesproject->id,
        );
        $this->create_object('org_openpsa_sales_salesproject_member_dba', $member_attributes);
        //remember to remove buddylist entry later on

        $deliverable_attributes = array
        (
           'salesproject' => $salesproject->id,
           'product' => $product->id,
           'description' => 'TEST DESCRIPTION',
           'plannedUnits' => 15,
        );
        $deliverable = $this->create_object('org_openpsa_sales_salesproject_dba', $deliverable_attributes);

        $start = time();
        $end = $start + (30 *24 * 60 * 60);
        $title = 'TEST TITLE';

        $start_cmp = mktime(0, 0, 0, date('n', $start), date('j', $start), date('Y', $start));
        $end_cmp = mktime(23, 59, 59, date('n', $end), date('j', $end), date('Y', $end));

        $scheduler = new org_openpsa_invoices_scheduler($deliverable);
        $_MIDCOM->auth->request_sudo('org.openpsa.invoices');
        $task = $scheduler->create_task($start, $end, $title);
        $this->assertTrue(is_a($task, 'org_openpsa_projects_task_dba'));
        $this->assertEquals($deliverable->id, $task->agreement);
        $this->assertEquals($salesproject->customer, $task->customer);
        $this->assertEquals($title, $task->title);
        $this->assertEquals($deliverable->description, $task->description);
        $this->assertEquals($start_cmp, $task->start);
        $this->assertEquals($end_cmp, $task->end);
        $this->assertEquals($deliverable->plannedUnits, $task->plannedHours);
        $this->assertEquals($salesproject->owner, $task->manager);
        $this->assertTrue($task->hoursInvoiceableDefault);

        $mc = org_openpsa_relatedto_dba::new_collector('fromGuid', $task->guid);
        $mc->add_value_property('toGuid');
        $mc->execute();
        $keys = $mc->list_keys();
        $this->assertEquals(1, sizeof($keys));
        $product_guid = $mc->get_subkey(key($keys), 'toGuid');
        $this->assertEquals($product->guid, $product_guid);

        $salesproject->get_members();
        $task->get_members();
        $this->assertEquals($salesproject->contacts, $task->contacts);

        $project = new org_openpsa_projects_project($task->up);
        $this->assertTrue(!empty($project->guid));

        $mc = org_openpsa_relatedto_dba::new_collector('fromGuid', $project->guid);
        $mc->add_value_property('toGuid');
        $mc->execute();
        $keys = $mc->list_keys();
        $this->assertEquals(1, sizeof($keys));
        $salesproject_guid = $mc->get_subkey(key($keys), 'toGuid');
        $this->assertEquals($salesproject->guid, $salesproject_guid);

        $project->get_members();
        $this->assertEquals($salesproject->contacts, $project->contacts);
        $this->assertEquals(array($salesproject->owner => true), $project->resources);

        $task->priority = 4;
        $task->manager = $member->id;
        $task->update();
        $task->add_members('resources', array($member->id));
        $task->refresh();
        $task2 = $scheduler->create_task($start, $end, $title, $task);
        $task2->get_members();
        $task->get_members();

        $this->assertEquals(4, $task2->priority);
        $this->assertEquals($member->id, $task2->manager);
        $this->assertEquals($task->resources, $task2->resources);


        $this->delete_linked_objects('org_openpsa_contacts_buddy_dba', 'account', $manager->guid);
        $task->delete();
        $task2->delete();
        $project->delete();
        $_MIDCOM->auth->drop_sudo();
    }
}
?>