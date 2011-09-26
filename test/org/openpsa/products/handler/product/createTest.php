<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR);
    require_once(OPENPSA_TEST_ROOT . 'rootfile.php');
}

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class org_openpsa_products_handler_product_createTest extends openpsa_testcase
{
    protected static $_group;

    public static function setUpBeforeClass()
    {
        self::$_group = self::create_class_object('org_openpsa_products_product_group_dba', array('code' => 'TEST_' . __CLASS__ . time()));
    }

    public function testHandler_create()
    {
        midcom::get('auth')->request_sudo('org.openpsa.products');

        $data = $this->run_handler('org.openpsa.products', array('product', 'create', 'default'));
        $this->assertEquals('create_product', $data['handler_id']);

        $formdata = array
        (
            'title' => 'TEST ' . __CLASS__ . ' ' . time(),
            'code' => 'TEST_' . __CLASS__ . '_' . time(),
            'delivery' => (string) ORG_OPENPSA_PRODUCTS_DELIVERY_SINGLE,
            'orgOpenpsaObtype' => (string) ORG_OPENPSA_PRODUCTS_PRODUCT_TYPE_GOODS,
            'org_openpsa_products_productGroup_chooser_widget_selections' => array((string) self::$_group->id => '1')
        );

        $url = $this->submit_dm2_form('controller', $formdata, 'org.openpsa.products', array('product', 'create', 'default'));

        $qb = org_openpsa_products_product_dba::new_query_builder();
        $qb->add_constraint('productGroup', '=', self::$_group->id);
        $results = $qb->execute();
        $this->register_objects($results);
        $this->assertEquals(1, sizeof($results));
        $this->assertEquals('product/' . $results[0]->guid . '/', $url);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_create_group()
    {
        midcom::get('auth')->request_sudo('org.openpsa.products');

        $data = $this->run_handler('org.openpsa.products', array('product', 'create', self::$_group->code, 'default'));
        $this->assertEquals('create_group_product', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }
}
?>