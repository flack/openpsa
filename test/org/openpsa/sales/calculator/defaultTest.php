<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\sales\calculator;

use openpsa_testcase;
use midcom;
use org_openpsa_invoices_invoice_dba;
use org_openpsa_sales_calculator_default;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class defaultTest extends openpsa_testcase
{
    public function testGenerate_invoice_number()
    {
        $qb = org_openpsa_invoices_invoice_dba::new_query_builder();
        $qb->add_order('number', 'DESC');
        $qb->set_limit(1);
        midcom::get()->auth->request_sudo('org.openpsa.invoices');
        $last_invoice = $qb->execute_unchecked();
        midcom::get()->auth->drop_sudo();

        if (count($last_invoice) == 0) {
            $previous = 0;
        } else {
            $previous = $last_invoice[0]->number;
        }

        $calculator = new org_openpsa_sales_calculator_default();

        $exp = $previous + 1;
        $stat = $calculator->generate_invoice_number();
        $this->assertEquals($exp, $stat);
    }
}
