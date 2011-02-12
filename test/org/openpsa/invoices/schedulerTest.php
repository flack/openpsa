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
        $deliverable = new org_openpsa_sales_salesproject_deliverable_dba();
        foreach ($attributes as $field => $value)
        {
            $deliverable->$field = $value;
        }

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
}
?>