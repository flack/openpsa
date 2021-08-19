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
class viewTest extends openpsa_testcase
{
    public function testHandler_comments()
    {
        $person = $this->create_user(true);
        midcom::get()->auth->request_sudo('net.nehmer.comments');

        $data = $this->run_handler('net.nehmer.comments', ['comment', $person->guid]);
        $this->assertEquals('view-comments', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_comments_nonempty()
    {
        $person = $this->create_user(true);
        midcom::get()->auth->request_sudo('net.nehmer.comments');

        $data = $this->run_handler('net.nehmer.comments', ['comment-nonempty', $person->guid]);
        $this->assertEquals('view-comments-nonempty', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
