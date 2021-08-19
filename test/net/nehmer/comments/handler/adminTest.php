<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\net\nehmer\comments\handler;

use openpsa_testcase;
use midcom;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class adminTest extends openpsa_testcase
{
    public function testHandler_welcome()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('net.nehmer.comments');

        $data = $this->run_handler('net.nehmer.comments');
        $this->assertEquals('admin-welcome', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    /**
     * @dataProvider provider_moderate
     */
    public function testHandler_moderate($status)
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('net.nehmer.comments');

        $data = $this->run_handler('net.nehmer.comments', ['moderate', $status]);
        $this->assertEquals('moderate', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function provider_moderate()
    {
        return [
            ['abuse'],
            ['reported_abuse'],
            ['junk'],
            ['latest'],
            ['latest_new'],
            ['latest_approved'],
        ];
    }
}
