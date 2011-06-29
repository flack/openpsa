<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . DIRECTORY_SEPARATOR);
    require_once(OPENPSA_TEST_ROOT . 'rootfile.php');
}

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class org_openpsa_sales_salesproject_deliverable_processTest extends openpsa_testcase
{
    protected static $_person;
    protected static $_salesproject;
    protected static $_product;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
        self::$_salesproject = self::create_class_object('org_openpsa_sales_salesproject_dba');
        $product_group = self::create_class_object('org_openpsa_products_product_group_dba');
        $product_attributes = array
        (
            'productGroup' => $product_group->id,
            'name' => 'TEST_' . __CLASS__ . '_' . time(),
        );
        self::$_product = self::create_class_object('org_openpsa_products_product_dba', $product_attributes);
    }

    public function testHandler_process()
    {
        midcom::get('auth')->request_sudo('org.openpsa.sales');

        $deliverable_attributes = array
        (
            'salesproject' => self::$_salesproject->id,
            'product' => self::$_product->id,
        );

        $deliverable = $this->create_object('org_openpsa_sales_salesproject_deliverable_dba', $deliverable_attributes);

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $_POST = array
        (
            'order' => true,
        );

        $data = $this->run_handler('org.openpsa.sales', array('deliverable', 'process', $deliverable->guid));
        $this->assertEquals('deliverable_process', $data['handler_id']);
        $deliverable->refresh();
        $this->assertEquals(org_openpsa_sales_salesproject_deliverable_dba::STATUS_ORDERED, $deliverable->state);

        $_POST = array
        (
            'deliver' => true,
        );
        $data = $this->run_handler('org.openpsa.sales', array('deliverable', 'process', $deliverable->guid));
        $this->assertEquals('deliverable_process', $data['handler_id']);
        $deliverable->refresh();
        $this->assertEquals(org_openpsa_sales_salesproject_deliverable_dba::STATUS_DELIVERED, $deliverable->state);

        midcom::get('auth')->drop_sudo();
    }

    public function tearDown()
    {
        parent::tearDown();
        if (isset($_SERVER['REQUEST_METHOD']))
        {
            unset($_SERVER['REQUEST_METHOD']);
        }
        if (!empty($_POST))
        {
            $_POST = array();
        }
    }
}
?>