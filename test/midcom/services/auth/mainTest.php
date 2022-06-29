<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\services\auth;

use openpsa_testcase;
use midcom_db_topic;
use midcom_core_user;
use midcom;
use midcom_core_context;
use Symfony\Component\HttpFoundation\Request;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class mainTest extends openpsa_testcase
{
    public function test_check_for_login_session()
    {
        $user = $this->create_user();
        $acl = new \midcom_services_auth_acl;
        $backend = new \midcom_services_auth_backend_simple('test');
        $frontend = new \midcom_services_auth_frontend_form;
        $auth = new \midcom_services_auth($acl, $backend, $frontend);
        $request = new Request([], [
            'username' => $user->lastname,
            'password' => $user->extra,
            'midcom_services_auth_frontend_form_submit' => ''
        ]);
        $auth->check_for_login_session($request);
        $this->assertEquals($user->guid, $auth->user->guid);
    }

    public function test_can_do()
    {
        $topic = $this->create_object(midcom_db_topic::class);
        $person = $this->create_user();
        $user = new midcom_core_user($person);

        $auth = midcom::get()->auth;

        $this->assertTrue($auth->can_do('midgard:read', $topic));
        $this->assertFalse($auth->can_do('midgard:delete', $topic));

        $auth->admin = true;
        $this->assertFalse($auth->can_do('midgard:delete', $topic));

        $auth->user = $user;
        $this->assertTrue($auth->can_do('midgard:delete', $topic));

        $auth->admin = false;
        $this->assertFalse($auth->can_do('midgard:delete', $topic));

        $person2 = $this->create_user();
        $user2 = new midcom_core_user($person2);
        $topic2 = $this->create_object(midcom_db_topic::class);
        midcom::get()->auth->request_sudo('midcom.core');
        $topic2->set_privilege('midgard:delete', $user2->id, MIDCOM_PRIVILEGE_ALLOW);
        midcom::get()->auth->drop_sudo();
        $auth->user = $user2;

        $this->assertTrue($auth->can_do('midgard:delete', $topic2));
    }

    public function test_can_user_do()
    {
        $person = $this->create_user();
        $user = new midcom_core_user($person);

        $auth = midcom::get()->auth;
        $auth->drop_sudo();
        $this->assertTrue($auth->can_user_do('midgard:read'));
        $this->assertFalse($auth->can_user_do('midgard:create'));

        $auth->user = $user;
        $auth->admin = true;
        $this->assertTrue($auth->can_user_do('midgard:create'));

        $auth->admin = false;
        $auth->request_sudo('midcom.core');
        $this->assertTrue($auth->can_user_do('midgard:create'));
        $auth->drop_sudo();

        $auth->user = $user;
        $this->assertFalse($auth->can_user_do('midgard:create'));

        $person2 = $this->create_user();
        $user2 = new midcom_core_user($person2);
        midcom::get()->auth->request_sudo('midcom.core');
        $person2->set_privilege('midgard:create', 'SELF', MIDCOM_PRIVILEGE_ALLOW);
        midcom::get()->auth->drop_sudo();

        $this->assertTrue($auth->can_user_do('midgard:create', $user2));
    }

    public function test_request_sudo()
    {
        $auth = midcom::get()->auth;

        $context = midcom_core_context::get();
        $context->set_key(MIDCOM_CONTEXT_COMPONENT, 'midcom.admin.folder');

        $this->assertTrue($auth->request_sudo());
        $this->assertTrue($auth->is_component_sudo());
        $auth->drop_sudo();
        $this->assertFalse($auth->is_component_sudo());
        $this->assertFalse($auth->request_sudo(''));
        $this->assertFalse($auth->is_component_sudo());
        $this->assertTrue($auth->request_sudo('some_string'));
        $auth->drop_sudo();

        midcom::get()->config->set('auth_allow_sudo', false);
        $stat = $auth->request_sudo();
        midcom::get()->config->set('auth_allow_sudo', true);
        $this->assertFalse($stat);
    }
}
