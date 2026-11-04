<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\calendar\handler;

use openpsa_testcase;
use midcom;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class resourceTest extends openpsa_testcase
{
    public function testHandler_create()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('org.openpsa.calendar');

        $data = $this->run_handler('org.openpsa.calendar', ['resource', 'new']);
        $this->assertEquals('new_resource', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
