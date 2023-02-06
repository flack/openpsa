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
use org_openpsa_products_product_group_dba;
use org_openpsa_products_product_dba;
use org_openpsa_sales_salesproject_deliverable_dba;
use Symfony\Component\Form\Form;
use midcom_services_at_entry_dba;
use org_openpsa_relatedto_plugin;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class adminTest extends openpsa_testcase
{
    protected static midcom_db_person $_person;
    protected static org_openpsa_sales_salesproject_dba $_salesproject;
    protected static org_openpsa_products_product_dba $_product;

    public static function setUpBeforeClass() : void
    {
        self::$_person = self::create_user(true);
        self::$_salesproject = self::create_class_object(org_openpsa_sales_salesproject_dba::class);
        $product_group = self::create_class_object(org_openpsa_products_product_group_dba::class);
        self::$_product = self::create_class_object(org_openpsa_products_product_dba::class, ['productGroup' => $product_group->id]);
    }

    public function testHandler_edit()
    {
        midcom::get()->auth->request_sudo('org.openpsa.sales');

        $deliverable_attributes = [
            'salesproject' => self::$_salesproject->id,
            'product' => self::$_product->id,
        ];

        $deliverable = $this->create_object(org_openpsa_sales_salesproject_deliverable_dba::class, $deliverable_attributes);
        $deliverable->set_parameter('midcom.helper.datamanager2', 'schema_name', 'subscription');

        $year = date('Y') + 1;
        $start = strtotime($year . '-10-15 00:00:00');

        $at_parameters = [
            'arguments' => [
                'deliverable' => $deliverable->guid,
                'cycle' => 1,
            ],
            'start' => $start,
            'component' => 'org.openpsa.sales',
            'method' => 'new_subscription_cycle'
        ];

        $at_entry = $this->create_object(midcom_services_at_entry_dba::class, $at_parameters);
        org_openpsa_relatedto_plugin::create($at_entry, 'midcom.services.at', $deliverable, 'org.openpsa.sales');

        $data = $this->run_handler('org.openpsa.sales', ['deliverable', 'edit', $deliverable->guid]);
        $this->assertEquals('deliverable_edit', $data['handler_id']);

        $group = $data['controller']->get_datamanager()->get_form()->get('next_cycle');

        $this->assertInstanceOf(Form::class, $group, 'next cycle widget missing');
        $unixtime = $group->getData();
        $this->assertEquals($year . '-10-15', date('Y-m-d', $unixtime));

        $formdata = [
            'next_cycle' => ['date' => ''],
            'title' => 'test',
            'start' => ['date' => '2012-10-10'],
            'end' => ['date' => $year . '-10-10'],
            'plannedUnits' => '1'
        ];

        $this->submit_dm_no_relocate_form('controller', $formdata, 'org.openpsa.sales', ['deliverable', 'edit', $deliverable->guid]);
        $this->assertCount(0, $deliverable->get_at_entries());

        midcom::get()->auth->drop_sudo();
    }
}
