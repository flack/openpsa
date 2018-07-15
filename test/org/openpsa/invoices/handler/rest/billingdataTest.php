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
class org_openpsa_invoices_handler_rest_billingdataTest extends openpsa_testcase
{
    protected static $_person;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
    }

    private function perform_get_request($params = [])
    {
        $_SERVER["REQUEST_METHOD"] = "GET";
        $_GET = $params;

        ob_start();
        $data = $this->run_handler('org.openpsa.invoices', ['rest', 'billingdata']);
        ob_end_clean();

        return $data['__openpsa_testcase_response'];
    }

    public function testHandler_get()
    {
        midcom::get()->auth->request_sudo('org.openpsa.invoices');

        // test invalid request
        $response = $this->perform_get_request([]); // invalid filter options, we need at least an id / guid

        $this->assertEquals(500, $response->code);
        $this->assertEquals($response->_data["message"], "Invalid filter options");

        // test valid request
        $response = $this->perform_get_request(["guid" => "", "linkGuid" => self::$_person->guid]);

        $obj = $response->_data["object"];

        // check some properties..
        $this->assertEquals(200, $response->code);
        $this->assertTrue(isset($obj->guid));
        $this->assertTrue(isset($obj->metadata));
        $this->assertEquals(self::$_person->guid, $obj->linkGuid);
        $this->assertEquals(self::$_person->guid, $obj->metadata->creator);
        $this->assertFalse($obj->metadata->deleted);

        midcom::get()->auth->drop_sudo();
    }
}
