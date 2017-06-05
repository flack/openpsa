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
class org_openpsa_invoices_handler_billingdataTest extends openpsa_testcase
{
    protected static $_person;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_billingdata()
    {
        $billingdata = $this->create_object('org_openpsa_invoices_billing_data_dba', ['linkGuid' => self::$_person->guid]);

        midcom::get()->auth->request_sudo('org.openpsa.invoices');

        $data = $this->run_handler('org.openpsa.invoices', ['billingdata', self::$_person->guid]);

        $this->assertEquals($billingdata->guid, $data['controller']->datamanager->storage->object->guid);

        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_create()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');

        $data = $this->run_handler('org.openpsa.invoices', ['billingdata', self::$_person->guid]);
        $this->assertTrue(is_a($data['controller'], 'midcom_helper_datamanager2_controller_create'));

        $object = $data['controller']->callback_object->dm2_create_callback($data['controller']->datamanager);
        $this->register_object($object);
        $this->assertEquals(self::$_person->guid, $object->linkGuid);

        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }
}
