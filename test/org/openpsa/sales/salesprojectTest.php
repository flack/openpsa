<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\sales;

use openpsa_testcase;
use org_openpsa_sales_salesproject_dba;
use midcom;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class salesprojectTest extends openpsa_testcase
{
    public function testCRUD()
    {
        midcom::get()->auth->request_sudo('org.openpsa.sales');

        $salesproject = new org_openpsa_sales_salesproject_dba();
        $salesproject->_use_rcs = false;

        $stat = $salesproject->create();
        $this->assertTrue($stat);
        $this->register_object($salesproject);
        $this->assertEquals(org_openpsa_sales_salesproject_dba::STATE_ACTIVE, $salesproject->state);

        $salesproject->refresh();
        $this->assertEquals('salesproject #' . $salesproject->id, $salesproject->title);
        $salesproject->title = 'Test Project';
        $stat = $salesproject->update();
        $this->assertTrue($stat);
        $this->assertEquals('Test Project', $salesproject->title);

        $stat = $salesproject->delete();
        $this->assertTrue($stat);

        midcom::get()->auth->drop_sudo();
    }
}
