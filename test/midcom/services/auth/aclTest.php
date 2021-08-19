<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\services\auth;

use openpsa_testcase;
use midcom_core_user;
use midcom_db_topic;
use midcom_db_article;
use midcom;
use midcom_db_group;
use midcom_db_member;
use midgard_topic;
use midcom_services_auth_acl;
use midcom_db_attachment;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class aclTest extends openpsa_testcase
{
    public function test_can_do_parent_object_privilege()
    {
        $person = $this->create_user();
        $user = new midcom_core_user($person);

        $person2 = $this->create_user();
        $user2 = new midcom_core_user($person2);

        $topic = $this->create_object(midcom_db_topic::class);
        $article = $this->create_object(midcom_db_article::class, ['topic' => $topic->id]);

        $topic_denied = $this->create_object(midcom_db_topic::class);
        $article_denied = $this->create_object(midcom_db_article::class, ['topic' => $topic_denied->id]);

        midcom::get()->auth->request_sudo('midcom.core');
        $person->set_privilege('midgard:read', 'SELF', MIDCOM_PRIVILEGE_DENY, 'midcom_db_article');
        $topic_denied->set_privilege('midgard:read', $user, MIDCOM_PRIVILEGE_DENY);
        $topic->set_privilege('midgard:update', $user2, MIDCOM_PRIVILEGE_ALLOW);
        midcom::get()->auth->drop_sudo();

        $auth = midcom::get()->auth;

        $this->assertTrue($auth->can_do('midgard:read', $article));
        $this->assertTrue($auth->can_do('midgard:read', $topic));
        $this->assertTrue($auth->can_do('midgard:read', $article_denied));

        $auth->user = $user;
        $this->assertTrue($auth->can_do('midgard:read', $topic));
        $this->assertFalse($auth->can_do('midgard:read', $article));
        $this->assertFalse($auth->can_do('midgard:read', $article_denied));

        $auth->user = $user2;
        $this->assertTrue($auth->can_do('midgard:read', $article));
        $this->assertTrue($auth->can_do('midgard:read', $article_denied));

        $this->assertTrue($auth->can_do('midgard:update', $topic));
        $this->assertTrue($auth->can_do('midgard:update', $article));
    }

    public function test_can_do_group_privilege()
    {
        $topic = $this->create_object(midcom_db_topic::class);
        $person = $this->create_user();
        $group = $this->create_object(midcom_db_group::class);
        $this->create_object(midcom_db_member::class, ['gid' => $group->id, 'uid' => $person->id]);

        midcom::get()->auth->request_sudo('midcom.core');
        $topic->set_privilege('midgard:read', 'group:' . $group->guid, MIDCOM_PRIVILEGE_DENY);
        $user = new midcom_core_user($person);
        midcom::get()->auth->drop_sudo();

        $auth = midcom::get()->auth;
        $auth->user = null;

        $this->assertTrue($auth->can_do('midgard:read', $topic));

        $auth->user = $user;
        $this->assertFalse($auth->can_do('midgard:read', $topic));
    }

    public function test_can_do_attachment()
    {
        // we use mgd class here to avoid populating static caches
        $topic = $this->create_object(midgard_topic::class);
        midcom::get()->auth->request_sudo('midcom.core');
        $att = $topic->create_attachment('test', 'test', 'text/html');
        midcom::get()->auth->drop_sudo();
        $acl = new midcom_services_auth_acl;
        $this->assertFalse($acl->can_do_byguid('midgard:update', $att->guid, midcom_db_attachment::class, 'ANONYMOUS'));
    }
}
