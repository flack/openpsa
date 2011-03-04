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
class org_openpsa_sales_salesprojectTest extends openpsa_testcase
{
    public function testCRUD()
    {
        $_MIDCOM->auth->request_sudo('org.openpsa.sales');

        $salesproject = new org_openpsa_sales_salesproject_dba();
        $stat = $salesproject->create();
        $this->assertTrue($stat);
        $this->assertEquals(ORG_OPENPSA_OBTYPE_SALESPROJECT, $salesproject->orgOpenpsaObtype);
        $this->assertEquals(ORG_OPENPSA_SALESPROJECTSTATUS_ACTIVE, $salesproject->status);

        $salesproject->refresh();
        $this->assertEquals('salesproject #' . $salesproject->id, $salesproject->title);
        $salesproject->title = 'Test Project';
        $stat = $salesproject->update();
        $this->assertTrue($stat);
        $this->assertEquals('Test Project', $salesproject->title);

        $stat = $salesproject->delete();
        $this->assertTrue($stat);

        $_MIDCOM->auth->drop_sudo();
     }
}
?>