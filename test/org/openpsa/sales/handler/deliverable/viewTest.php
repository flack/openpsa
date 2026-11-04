<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\sales\handler\deliverable;

use midcom_db_person;
use openpsa_testcase;
use midcom;
use org_openpsa_sales_salesproject_dba;
use org_openpsa_sales_salesproject_deliverable_dba;
use org_openpsa_products_product_dba;
use org_openpsa_products_product_group_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class viewTest extends openpsa_testcase
{
    protected static midcom_db_person $_person;
    protected static org_openpsa_sales_salesproject_dba $_salesproject;

    public static function setUpBeforeClass() : void
    {
        self::$_person = self::create_user(true);
        self::$_salesproject = self::create_class_object(org_openpsa_sales_salesproject_dba::class);
    }

    public function testHandler_process()
    {
        midcom::get()->auth->request_sudo('org.openpsa.sales');

        $deliverable_attributes = [
            'salesproject' => self::$_salesproject->id,
        ];

        $deliverable = $this->create_object(org_openpsa_sales_salesproject_deliverable_dba::class, $deliverable_attributes);

        $data = $this->run_handler('org.openpsa.sales', ['deliverable', $deliverable->guid]);
        $this->assertEquals('deliverable_view', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_run_cycle()
    {
        midcom::get()->auth->request_sudo('org.openpsa.sales');

        $product_group = $this->create_object(org_openpsa_products_product_group_dba::class);
        $product = $this->create_object(org_openpsa_products_product_dba::class, [
            'productGroup' => $product_group->id,
            'delivery' => org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION
        ]);
        $salesproject = $this->create_object(org_openpsa_sales_salesproject_dba::class, ['customerContact' => self::$_person->id]);
        $deliverable = $this->create_object(org_openpsa_sales_salesproject_deliverable_dba::class, [
            'salesproject' => $salesproject->id,
            'product' => $product->id,
            'start' => time(),
            'continuous' => true,
            'unit' => 'q',
            'orgOpenpsaObtype' => org_openpsa_products_product_dba::DELIVERY_SUBSCRIPTION
        ]);
        $this->assertTrue($deliverable->order());
        $this->register_objects($deliverable->get_at_entries());

        $this->set_post_data([]);
        $url = $this->run_relocate_handler('org.openpsa.sales', ['deliverable', 'run_cycle', $deliverable->guid]);
        $this->assertEquals('deliverable/' . $deliverable->guid . '/', $url);

        $this->register_objects($deliverable->get_at_entries());

        midcom::get()->auth->drop_sudo();
    }
}
