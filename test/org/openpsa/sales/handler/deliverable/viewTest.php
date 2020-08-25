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
class org_openpsa_sales_salesproject_deliverable_viewTest extends openpsa_testcase
{
    protected static $_person;
    protected static $_salesproject;

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
}
