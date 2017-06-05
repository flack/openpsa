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
class net_nehmer_comments_handler_adminTest extends openpsa_testcase
{
    public function testHandler_welcome()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('net.nehmer.comments');

        $data = $this->run_handler('net.nehmer.comments');
        $this->assertEquals('admin-welcome', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_moderate()
    {
        $this->create_user(true);
        midcom::get()->auth->request_sudo('net.nehmer.comments');

        $data = $this->run_handler('net.nehmer.comments', ['moderate', 'reported_abuse']);
        $this->assertEquals('moderate', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
