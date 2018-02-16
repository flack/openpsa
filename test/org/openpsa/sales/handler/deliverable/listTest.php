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
class org_openpsa_sales_salesproject_deliverable_listTest extends openpsa_testcase
{
    protected static $_person;
    protected static $_salesproject;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_list()
    {
        midcom::get()->auth->request_sudo('org.openpsa.sales');
        $product_group = self::create_class_object(org_openpsa_products_product_group_dba::class);
        $product_attributes = [
            'productGroup' => $product_group->id,
            'name' => 'TEST_' . __CLASS__ . '_' . time(),
        ];
        $product = self::create_class_object(org_openpsa_products_product_dba::class, $product_attributes);

        $data = $this->run_handler('org.openpsa.sales', ['deliverable', 'list', 'product', $product->guid]);
        $this->assertEquals('deliverable_list_product', $data['handler_id']);

        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }
}
