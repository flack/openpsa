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
class org_openpsa_sales_handler_listTest extends openpsa_testcase
{
    protected static $_person;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_list()
    {
        midcom::get()->auth->request_sudo('org.openpsa.sales');

        $data = $this->run_handler('org.openpsa.sales');
        $this->assertEquals('frontpage', $data['handler_id']);

        $data = $this->run_handler('org.openpsa.sales', ['list', 'lost']);
        $this->assertEquals('list_state', $data['handler_id']);

        $data = $this->run_handler('org.openpsa.sales', ['list', 'canceled']);
        $this->assertEquals('list_state', $data['handler_id']);

        $data = $this->run_handler('org.openpsa.sales', ['list', 'active']);
        $this->assertEquals('list_state', $data['handler_id']);

        $data = $this->run_handler('org.openpsa.sales', ['list', 'won']);
        $this->assertEquals('list_state', $data['handler_id']);

        $data = $this->run_handler('org.openpsa.sales', ['list', 'delivered']);
        $this->assertEquals('list_state', $data['handler_id']);

        $data = $this->run_handler('org.openpsa.sales', ['list', 'invoiced']);
        $this->assertEquals('list_state', $data['handler_id']);

        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_list_customer()
    {
        midcom::get()->auth->request_sudo('org.openpsa.sales');

        $data = $this->run_handler('org.openpsa.sales', ['list', 'customer', self::$_person->guid]);
        $this->assertEquals('list_customer', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
