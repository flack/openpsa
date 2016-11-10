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
class org_openpsa_products_handler_group_listTest extends openpsa_testcase
{
    protected static $_group;

    public static function setUpBeforeClass()
    {
        self::$_group = self::create_class_object('org_openpsa_products_product_group_dba', array('code' => 'TEST_' . __CLASS__ . time()));
    }

    public function testHandler_index()
    {
        midcom::get()->auth->request_sudo('org.openpsa.products');

        $data = $this->run_handler('org.openpsa.products');
        $this->assertEquals('index', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_list()
    {
        midcom::get()->auth->request_sudo('org.openpsa.products');

        $data = $this->run_handler('org.openpsa.products', array(self::$_group->code));
        $this->assertEquals('list', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_listall()
    {
        midcom::get()->auth->request_sudo('org.openpsa.products');

        $group_attributes = array(
            'code' => 'TEST_' . __FUNCTION__ . time(),
            'up' => self::$_group->id
        );

        $childgroup = $this->create_object('org_openpsa_products_product_group_dba', $group_attributes);

        $data = $this->run_handler('org.openpsa.products', array('list', self::$_group->code, $childgroup->code));
        $this->assertEquals('listall', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_listintree()
    {
        midcom::get()->auth->request_sudo('org.openpsa.products');

        $group_attributes = array(
            'code' => 'TEST_' . __FUNCTION__ . time(),
            'up' => self::$_group->id
        );

        $childgroup = $this->create_object('org_openpsa_products_product_group_dba', $group_attributes);

        $data = $this->run_handler('org.openpsa.products', array(self::$_group->code, $childgroup->code));
        $this->assertEquals('list_intree', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
