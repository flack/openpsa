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
class org_openpsa_invoices_handler_billingdataTest extends openpsa_testcase
{
    protected static $_person;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_billingdata()
    {
        midcom::get('auth')->request_sudo('org.openpsa.invoices');
        $billingdata = $this->create_object('org_openpsa_invoices_billing_data_dba', array('linkGuid' => self::$_person->guid));

        $data = $this->run_handler('org.openpsa.invoices', array('billingdata', $billingdata->guid));
        $this->assertEquals($billingdata->guid, $data['controller']->datamanager->storage->object->guid);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_create()
    {
        midcom::get('auth')->request_sudo('org.openpsa.invoices');

        $data = $this->run_handler('org.openpsa.invoices', array('billingdata', 'create', self::$_person->guid));
        $this->assertTrue(is_a($data['controller'], 'midcom_helper_datamanager2_controller_create'));

        $object = $data['controller']->callback_object->dm2_create_callback($data['controller']->datamanager);
        $this->register_object($object);
        $this->assertEquals(self::$_person->guid, $object->linkGuid);

        midcom::get('auth')->drop_sudo();
    }
}
?>