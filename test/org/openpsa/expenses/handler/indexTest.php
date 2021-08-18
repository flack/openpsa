<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\expenses\handler;

use openpsa_testcase;
use midcom;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class indexTest extends openpsa_testcase
{
    public static function setUpBeforeClass() : void
    {
        self::create_user(true);
    }

    public function testHandler_index()
    {
        midcom::get()->auth->request_sudo('org.openpsa.expenses');

        $data = $this->run_handler('org.openpsa.expenses');
        $this->assertEquals('index', $data['handler_id']);
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_index_timestamp()
    {
        midcom::get()->auth->request_sudo('org.openpsa.expenses');

        $data = $this->run_handler('org.openpsa.expenses', ['2011-01-26']);
        $this->assertEquals('index_timestamp', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
