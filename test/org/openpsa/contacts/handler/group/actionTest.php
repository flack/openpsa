<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . DIRECTORY_SEPARATOR);
    require_once(OPENPSA_TEST_ROOT . 'rootfile.php');
}

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class org_openpsa_contacts_handler_group_actionTest extends openpsa_testcase
{
    protected static $_person;
    protected static $_group;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
        self::$_group = self::create_class_object('org_openpsa_contacts_group_dba');
    }

    public function testHandler_action_members()
    {
        midcom::get('auth')->request_sudo('org.openpsa.contacts');

        $data = $this->run_handler('org.openpsa.contacts', array('group', self::$_group->guid, 'members'));
        $this->assertEquals('group_action', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }
}
?>