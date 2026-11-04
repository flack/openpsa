<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\sales\handler\rest;

use midcom_db_person;
use openpsa_testcase;
use midcom;
use org_openpsa_sales_salesproject_dba;
use org_openpsa_sales_salesproject_deliverable_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class deliverableTest extends openpsa_testcase
{
    protected static midcom_db_person $_person;
    protected static org_openpsa_sales_salesproject_dba $_salesproject;

    public static function setUpBeforeClass() : void
    {
        self::$_person = self::create_user(true);
        self::$_salesproject = self::create_class_object(org_openpsa_sales_salesproject_dba::class);
    }

    public function testHandler_get()
    {
        midcom::get()->auth->request_sudo('org.openpsa.sales');

        $deliverable = $this->create_object(org_openpsa_sales_salesproject_deliverable_dba::class, ['salesproject' => self::$_salesproject->id]);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['guid' => $deliverable->guid];

        $data = $this->run_handler('org.openpsa.sales', ['rest', 'deliverable']);
        $this->assertEquals('deliverable_rest', $data['handler_id']);

        $response = $data['__openpsa_testcase_response'];
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($deliverable->guid, json_decode($response->getContent())->object->guid);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_update()
    {
        midcom::get()->auth->request_sudo('org.openpsa.sales');

        $deliverable = $this->create_object(org_openpsa_sales_salesproject_deliverable_dba::class, ['salesproject' => self::$_salesproject->id]);

        $this->set_post_data([
            'id' => $deliverable->id,
            'title' => __FUNCTION__
        ]);

        $data = $this->run_handler('org.openpsa.sales', ['rest', 'deliverable']);
        $this->assertEquals(200, $data['__openpsa_testcase_response']->getStatusCode());

        $deliverable->refresh();
        $this->assertEquals(__FUNCTION__, $deliverable->title);

        midcom::get()->auth->drop_sudo();
    }
}
