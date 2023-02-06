<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\products\handler\product;

use openpsa_testcase;
use midcom;
use org_openpsa_products_product_group_dba;
use org_openpsa_products_product_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class createTest extends openpsa_testcase
{
    protected static org_openpsa_products_product_group_dba $_group;

    public static function setUpBeforeClass() : void
    {
        self::$_group = self::create_class_object(org_openpsa_products_product_group_dba::class, ['code' => 'TEST_' . __CLASS__ . time()]);
    }

    public function testHandler_create()
    {
        midcom::get()->auth->request_sudo('org.openpsa.products');

        $data = $this->run_handler('org.openpsa.products', ['product', 'create', 'default']);
        $this->assertEquals('create_product', $data['handler_id']);

        $formdata = [
            'title' => 'TEST ' . __CLASS__ . ' ' . time(),
            'code' => 'TEST_' . __CLASS__ . '_' . time(),
            'delivery' => (string) org_openpsa_products_product_dba::DELIVERY_SINGLE,
            'orgOpenpsaObtype' => (string) org_openpsa_products_product_dba::TYPE_GOODS,
            'productGroup' => [
                'selection' => '[' . self::$_group->id . ']'
            ],
            'tags' => 'tag1'
        ];

        $this->submit_dm_no_relocate_form('controller', $formdata, 'org.openpsa.products', ['product', 'create', 'default']);
        $url = $this->get_dialog_url();
        $qb = org_openpsa_products_product_dba::new_query_builder();
        $qb->add_constraint('productGroup', '=', self::$_group->id);
        $results = $qb->execute();
        $this->register_objects($results);
        $this->assertCount(1, $results);
        $this->assertEquals('product/' . $results[0]->guid . '/', $url);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_create_group()
    {
        midcom::get()->auth->request_sudo('org.openpsa.products');

        $data = $this->run_handler('org.openpsa.products', ['product', 'create', self::$_group->id, 'default']);
        $this->assertEquals('create_group_product', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
