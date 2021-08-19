<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midgard\admin\user\handler;

use openpsa_testcase;
use midcom;
use midcom_db_person;
use midcom_db_group;
use midcom_db_member;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class listTest extends openpsa_testcase
{
    public function testHandler_list()
    {
        midcom::get()->auth->request_sudo('midgard.admin.user');

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard_midgard.admin.user']);
        $this->assertEquals('user_list', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_list_search()
    {
        $person = $this->create_object(midcom_db_person::class, [
            'lastname' => uniqid()
        ]);
        $group = $this->create_object(midcom_db_group::class);
        $this->create_object(midcom_db_member::class, [
            'gid' => $group->id,
            'uid' => $person->id
        ]);
        midcom::get()->auth->request_sudo('midgard.admin.user');
        $_REQUEST['midgard_admin_user_search'] = $person->lastname;

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard_midgard.admin.user']);
        $this->assertEquals('user_list', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_password_email()
    {
        midcom::get()->auth->request_sudo('midgard.admin.user');

        $data = $this->run_handler('net.nehmer.static', ['__mfa', 'asgard_midgard.admin.user', 'password', 'email']);
        $this->assertEquals('user_password_email', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_batch()
    {
        midcom::get()->auth->request_sudo('midgard.admin.user');
        $person = $this->create_user();
        $_POST = [
            'midgard_admin_user' => [$person->guid]
        ];
        $url = $this->run_relocate_handler('net.nehmer.static', ['__mfa', 'asgard_midgard.admin.user', 'batch', 'removeaccount']);
        $this->assertEquals('__mfa/asgard_midgard.admin.user/', $url);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_batch_groupadd()
    {
        midcom::get()->auth->request_sudo('midgard.admin.user');
        $person = $this->create_user();
        $_POST = [
            'midgard_admin_user' => [$person->guid]
        ];
        $url = $this->run_relocate_handler('net.nehmer.static', ['__mfa', 'asgard_midgard.admin.user', 'batch', 'groupadd']);
        $this->assertEquals('__mfa/asgard_midgard.admin.user/', $url);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_batch_passwords()
    {
        midcom::get()->auth->request_sudo('midgard.admin.user');
        $person = $this->create_user();
        $_POST = [
            'midgard_admin_user' => [$person->guid]
        ];
        $url = $this->run_relocate_handler('net.nehmer.static', ['__mfa', 'asgard_midgard.admin.user', 'batch', 'passwords']);
        $this->assertEquals('__mfa/asgard_midgard.admin.user/', $url);

        midcom::get()->auth->drop_sudo();
    }
}
