<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\sales\handler;

use midcom_db_person;
use openpsa_testcase;
use org_openpsa_sales_salesproject_dba;
use test_offer_pdfbuilder;
use org_openpsa_sales_salesproject_offer_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class offerTest extends openpsa_testcase
{
    protected static midcom_db_person $_person;
    protected static org_openpsa_sales_salesproject_dba $_salesproject;

    public static function setUpBeforeClass() : void
    {
        require_once dirname(__DIR__) . '/__helper/offer_pdfbuilder.php';
        self::$_person = self::create_user(true);
        self::$_salesproject = self::create_class_object(org_openpsa_sales_salesproject_dba::class, ['customerContact' => self::$_person->id]);
    }

    public function testHandler_create()
    {
        $this->set_config('org.openpsa.sales', 'sales_pdfbuilder_class', test_offer_pdfbuilder::class);

        $data = $this->run_handler('org.openpsa.sales', ['salesproject', 'offer', self::$_salesproject->guid]);
        $this->assertEquals('create_offer', $data['handler_id']);
    }

    public function testHandler_edit()
    {
        $this->set_config('org.openpsa.sales', 'sales_pdfbuilder_class', test_offer_pdfbuilder::class);

        $offer = $this->create_object(org_openpsa_sales_salesproject_offer_dba::class, [
            'salesproject' => self::$_salesproject->id
        ]);

        $data = $this->run_handler('org.openpsa.sales', ['salesproject', 'offer', 'edit', $offer->guid]);
        $this->assertEquals('edit_offer', $data['handler_id']);
    }

    public function testHandler_delete()
    {
        $offer = $this->create_object(org_openpsa_sales_salesproject_offer_dba::class, [
            'salesproject' => self::$_salesproject->id
        ]);

        $data = $this->run_handler('org.openpsa.sales', ['salesproject', 'offer', 'delete', $offer->guid]);
        $this->assertEquals('delete_offer', $data['handler_id']);
    }
}
