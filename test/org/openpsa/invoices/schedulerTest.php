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
class org_openpsa_invoices_schedulerTest extends openpsa_testcase
{
    protected $_salesproject;

    /**
     * @dataProvider providerCalculate_cycle_next
     */
    public function testCalculate_cycle_next($unit, $start, $result)
    {
        $old = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $start = strtotime($start);
        date_default_timezone_set($old);

        $deliverable = new org_openpsa_sales_salesproject_deliverable_dba();
        $deliverable->unit = $unit;

        $scheduler = new org_openpsa_invoices_scheduler($deliverable);
        $next_cycle = $scheduler->calculate_cycle_next($start);

        if ($next_cycle !== false) {
            $next_cycle = gmstrftime('%Y-%m-%d %H:%M:%S', $next_cycle);
        }

        $this->assertEquals($result, $next_cycle, 'Wrong value for unit ' . $unit . ', start value: ' . $start);
    }

    public function providerCalculate_cycle_next()
    {
        return [
            [
                'd',
                '2011-02-12 00:00:00',
                '2011-02-13 00:00:00',
            ],
            [
                'm',
                '2011-02-12 00:00:00',
                '2011-03-12 00:00:00',
            ],
            [
                'm',
                '2013-01-01 00:00:00',
                '2013-02-01 00:00:00',
            ],
            [
                'm',
                '2012-09-01 02:00:00',
                '2012-10-01 00:00:00',
            ],
            [
                'm',
                '2011-02-01 00:00:00',
                '2011-03-01 00:00:00',
            ],
            [
                'm',
                '2012-10-31 02:42:52',
                '2012-11-30 00:00:00',
            ],
            [
                'q',
                '2011-02-12 00:00:00',
                '2011-05-12 00:00:00',
            ],
            [
                'y',
                '2011-02-12 00:00:00',
                '2012-02-12 00:00:00',
            ],
            [
                'x',
                '2011-02-12 00:00:00',
                false,
            ]
        ];
    }

    /**
     * @dataProvider providerGet_cycle_identifier
     */
    public function testGet_cycle_identifier($attributes, $output)
    {
        $deliverable = self::prepare_object('org_openpsa_sales_salesproject_deliverable_dba', $attributes);
        $scheduler = new org_openpsa_invoices_scheduler($deliverable);
        $identifier = $scheduler->get_cycle_identifier($deliverable->start);
        $this->assertEquals($identifier, $output);
    }

    public function providerGet_cycle_identifier()
    {
        return [
            [
                [
                    'unit' => 'd',
                    'start' => 1293840000,
                    'end' => 1325376000,
                ],
                '2011-01-01',
            ],
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

    /**
     * @dataProvider providerCalculate_cycles
     * @depends testCalculate_cycle_next
     */
    public function testCalculate_cycles($attributes, $months, $result)
    {
        $deliverable = self::prepare_object('org_openpsa_sales_salesproject_deliverable_dba', $attributes);

        $scheduler = new org_openpsa_invoices_scheduler($deliverable);
        $cycles = $scheduler->calculate_cycles($months, $attributes['start']);

        $this->assertEquals($result, $cycles, 'Wrong value for unit ' . $deliverable->unit);
    }

    public function providerCalculate_cycles()
    {
        return [
            [
                [
                    'unit' => 'd',
                    'start' => 1293840000,
                    'end' => 1325376000,
                ],
                null,
                365,
            ],
            [
                [
                    'unit' => 'm',
                    'start' => 1293840000,
                    'end' => 1325376000,
                ],
                null,
                12,
            ],
            [
                [
                    'unit' => 'y',
                    'start' => 1293840000,
                    'end' => 1325376000,
                ],
                null,
                1,
            ],
            [
                [
                    'unit' => 'hy',
                    'start' => 1293840000,
                    'end' => 1325376000,
                ],
                null,
                2,
            ],
            [
                [
                    'unit' => 'q',
                    'start' => 1293840000,
                    'end' => 1325376000,
                ],
                null,
                4,
            ],
            [
                [
                    'unit' => 'd',
                    'start' => 1293840000,
                    'end' => 1325376000,
                ],
                1,
                31,
             ],
        ];
    }

    public function testCreate_task()
    {
        $organization = $this->create_object('org_openpsa_contacts_group_dba');
        $manager = $this->create_object('midcom_db_person');
        $member = $this->create_object('midcom_db_person');

        $group = $this->create_object('org_openpsa_products_product_group_dba');

        $product_attributes = [
            'productGroup' => $group->id,
            'code' => 'TEST-' . __CLASS__ . time(),
        ];
        $product = $this->create_object('org_openpsa_products_product_dba', $product_attributes);

        $salesproject_attributes = [
            'owner' => $manager->id,
            'customer' => $organization->id,
        ];
        $salesproject = $this->create_object('org_openpsa_sales_salesproject_dba', $salesproject_attributes);

        $member_attributes = [
            'person' => $member->id,
            'objectGuid' => $salesproject->guid,
            'role' => org_openpsa_sales_salesproject_dba::ROLE_MEMBER
        ];
        $this->create_object('org_openpsa_contacts_role_dba', $member_attributes);

        $deliverable_attributes = [
           'salesproject' => $salesproject->id,
           'product' => $product->id,
           'description' => 'TEST DESCRIPTION',
           'plannedUnits' => 15,
        ];
        $deliverable = $this->create_object('org_openpsa_sales_salesproject_deliverable_dba', $deliverable_attributes);

        $start = time();
        $end = $start + (30 *24 * 60 * 60);
        $title = 'TEST TITLE';

        $start_cmp = mktime(0, 0, 0, date('n', $start), date('j', $start), date('Y', $start));
        $end_cmp = mktime(23, 59, 59, date('n', $end), date('j', $end), date('Y', $end));

        $scheduler = new org_openpsa_invoices_scheduler($deliverable);
        midcom::get()->auth->request_sudo('org.openpsa.invoices');
        $task = $scheduler->create_task($start, $end, $title);
        $this->assertTrue(is_a($task, 'org_openpsa_projects_task_dba'));
        $this->register_object($task);

        $this->assertEquals($deliverable->id, $task->agreement);
        $this->assertEquals($salesproject->customer, $task->customer);
        $this->assertEquals($title, $task->title);
        $this->assertEquals($deliverable->description, $task->description);
        $this->assertEquals($start_cmp, $task->start);
        $this->assertEquals($end_cmp, $task->end);
        $this->assertEquals($deliverable->plannedUnits, $task->plannedHours);
        $this->assertEquals($salesproject->owner, $task->manager);
        $this->assertTrue($task->hoursInvoiceableDefault);

        $salesproject->get_members();
        $task->get_members();
        $this->assertEquals($salesproject->contacts, $task->contacts);

        $project = new org_openpsa_projects_project($task->project);
        $this->assertTrue(!empty($project->guid));
        $this->register_object($project);

        $project->get_members();
        $this->assertEquals($salesproject->contacts, $project->contacts);
        $this->assertEquals($salesproject->owner, $project->manager);

        $task->priority = 4;
        $task->manager = $member->id;
        $task->update();
        $task->add_members('resources', [$member->id]);
        $task->refresh();
        $task2 = $scheduler->create_task($start, $end, $title, $task);
        $this->register_object($task2);
        $task2->get_members();
        $task->get_members();

        $this->assertEquals(4, $task2->priority);
        $this->assertEquals($member->id, $task2->manager);
        $this->assertEquals($task->resources, $task2->resources);

        midcom::get()->auth->drop_sudo();
    }
}
