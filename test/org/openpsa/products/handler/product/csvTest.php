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
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class csvTest extends openpsa_testcase
{
    public static function setUpBeforeClass() : void
    {
        self::create_user(true);
    }

    public function testHandler_csv_redirects_to_filename()
    {
        midcom::get()->auth->request_sudo('org.openpsa.products');

        $url = $this->run_relocate_handler('org.openpsa.products', ['export', 'product', 'csv']);
        $this->assertStringEndsWith(date('Y-m-d') . '.csv', $url);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_csv_filename()
    {
        midcom::get()->auth->request_sudo('org.openpsa.products');

        $data = $this->run_handler('org.openpsa.products', ['export', 'product', 'csv', 'default.csv']);
        $this->assertEquals('export_product_csv_schemaname', $data['handler_id']);
        $this->assertInstanceOf(StreamedResponse::class, $data['__openpsa_testcase_response']);
        $this->assertStringEndsWith('default_' . date('Y-m-d') . '.csv', $data['filename']);

        midcom::get()->auth->drop_sudo();
    }
}
