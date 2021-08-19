<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\invoices\handler;

use openpsa_testcase;
use midcom;
use midcom\datamanager\controller;
use org_openpsa_invoices_billing_data_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class billingdataTest extends openpsa_testcase
{
    protected static $_person;

    public static function setUpBeforeClass() : void
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_billingdata()
    {
        $billingdata = $this->create_object(org_openpsa_invoices_billing_data_dba::class, ['linkGuid' => self::$_person->guid]);

        midcom::get()->auth->request_sudo('org.openpsa.invoices');

        $data = $this->run_handler('org.openpsa.invoices', ['billingdata', self::$_person->guid]);

        $this->assertEquals($billingdata->guid, $data['controller']->get_datamanager()->get_storage()->get_value()->guid);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_create()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');

        $data = $this->run_handler('org.openpsa.invoices', ['billingdata', self::$_person->guid]);
        $this->assertInstanceOf(controller::class, $data['controller']);

        $object = $data['controller']->get_datamanager()->get_storage()->get_value();
        $this->assertEquals(self::$_person->guid, $object->linkGuid);

        midcom::get()->auth->drop_sudo();
    }
}
