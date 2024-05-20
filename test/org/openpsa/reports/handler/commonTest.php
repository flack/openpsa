<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\reports\handler;

use openpsa_testcase;
use midcom;
use org_openpsa_reports_query_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class commonTest extends openpsa_testcase
{
    public static function setUpBeforeClass() : void
    {
        self::create_user(true);
    }

    public function test_handler_frontpage()
    {
        midcom::get()->auth->request_sudo('org.openpsa.reports');

        $data = $this->run_handler('org.openpsa.reports');
        $this->assertEquals('frontpage', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function test_handler_csv()
    {
        midcom::get()->auth->request_sudo('org.openpsa.reports');

        $_POST = ['org_openpsa_reports_csv' => 'TEST'];

        $data = $this->run_handler('org.openpsa.reports', 'csv/testfile.csv');
        $this->assertEquals('csv_export', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function test_handler_delete_report()
    {
        midcom::get()->auth->request_sudo('org.openpsa.reports');

        $query = $this->create_object(org_openpsa_reports_query_dba::class);

        $url = $this->run_relocate_handler('org.openpsa.reports', ['delete', $query->guid]);
        $this->assertEquals('', $url);

        midcom::get()->auth->drop_sudo();
    }
}
