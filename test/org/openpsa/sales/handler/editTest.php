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
class org_openpsa_sales_handler_editTest extends openpsa_testcase
{
    protected static $_person;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_edit()
    {
        midcom::get()->auth->request_sudo('org.openpsa.sales');

        $salesproject = $this->create_object(org_openpsa_sales_salesproject_dba::class);

        $data = $this->run_handler('org.openpsa.sales', ['salesproject', 'edit', $salesproject->guid]);
        $this->assertEquals('salesproject_edit', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_new()
    {
        midcom::get()->auth->request_sudo('org.openpsa.sales');

        $data = $this->run_handler('org.openpsa.sales', ['salesproject', 'new']);
        $this->assertEquals('salesproject_new', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
