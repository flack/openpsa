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
class org_openpsa_sales_handler_viewTest extends openpsa_testcase
{
    protected static $_person;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_view()
    {
        midcom::get()->auth->request_sudo('org.openpsa.sales');

        $salesproject = $this->create_object(org_openpsa_sales_salesproject_dba::class);
        $product_group = $this->create_object(org_openpsa_products_product_group_dba::class);
        $product_attributes = [
            'orgOpenpsaObtype' => org_openpsa_products_product_dba::TYPE_SERVICE,
            'productGroup' => $product_group->id,
            'name' => __CLASS__ . __FUNCTION__ . time()
        ];
        $product = $this->create_object(org_openpsa_products_product_dba::class, $product_attributes);
        $deliverable_attributes = [
            'salesproject' => $salesproject->id,
            'product' => $product->id,
            'state' => org_openpsa_sales_salesproject_deliverable_dba::STATE_ORDERED
        ];

        $deliverable = $this->create_object(org_openpsa_sales_salesproject_deliverable_dba::class, $deliverable_attributes);

        $data = $this->run_handler('org.openpsa.sales', ['salesproject', $salesproject->guid]);
        $this->assertEquals('salesproject_view', $data['handler_id']);

        $this->show_handler($data);

        midcom::get()->auth->drop_sudo();
    }
}
