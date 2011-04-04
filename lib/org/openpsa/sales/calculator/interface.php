<?php
/**
 * @package org.openpsa.sales
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Interface for deliverable cost/price calculators
 *
 * @package org.openpsa.sales
 */
interface org_openpsa_sales_calculator_interface
{
    /**
     * Constructor
     */
    public function __construct(org_openpsa_sales_salesproject_deliverable_dba $deliverable);

    /**
     * Perform the cost/price calculation
     */
    public function run();

    /**
     * Returns the calculated cost
     *
     * @return float cost value
     */
    public function get_cost();

    /**
     * Returns the calculated price
     *
     * @return float price value
     */
    public function get_price();

}
?>