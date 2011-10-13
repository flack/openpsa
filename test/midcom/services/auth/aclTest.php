<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR);
    require_once(OPENPSA_TEST_ROOT . 'rootfile.php');
}

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_services_auth_aclTest extends openpsa_testcase
{
    public function test_can_do_inherited_privilege()
    {
        $topic = $this->create_object('midcom_db_topic');
        $article = $this->create_object('midcom_db_article', array('topic' => $topic->id));
        $person = $this->create_user();

        midcom::get('auth')->request_sudo('midcom.core');
        $person->set_privilege('midgard:read', 'SELF', MIDCOM_PRIVILEGE_DENY, 'midcom_db_article');
        $user = new midcom_core_user($person);
        midcom::get('auth')->drop_sudo();

        $auth = new midcom_services_auth;
        $auth->initialize();

        $this->assertTrue($auth->can_do('midgard:read', $article));
        $this->assertTrue($auth->can_do('midgard:read', $topic));

        $auth->user = $user;
        $this->assertTrue($auth->can_do('midgard:read', $topic));
        $this->assertFalse($auth->can_do('midgard:read', $article));

        $person2 = $this->create_user();
        $user2 = new midcom_core_user($person2);
        $auth->user = $user2;

        $this->assertTrue($auth->can_do('midgard:read', $article));
    }
}
?>