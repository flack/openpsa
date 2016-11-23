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
class org_openpsa_products_handler_product_viewTest extends openpsa_testcase
{
    protected static $_product;
    protected static $_group;

    public static function setUpBeforeClass()
    {
        self::$_group = self::create_class_object('org_openpsa_products_product_group_dba', array('code' => 'TEST_' . __CLASS__ . time()));
        self::$_product = self::create_class_object('org_openpsa_products_product_dba', array('productGroup' => self::$_group->id));
    }

    public function testHandler_view()
    {
        midcom::get()->auth->request_sudo('org.openpsa.products');

        $data = $this->run_handler('org.openpsa.products', array('product', self::$_product->guid));
        $this->assertEquals('view_product', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_view_raw()
    {
        midcom::get()->auth->request_sudo('org.openpsa.products');

        $data = $this->run_handler('org.openpsa.products', array('product', 'raw', self::$_product->guid));
        $this->assertEquals('view_product_raw', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
