<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR);
    require_once(OPENPSA_TEST_ROOT . 'rootfile.php');
}

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midgard_admin_asgard_handler_undeleteTest extends openpsa_testcase
{
    public function testHandler_trash()
    {
        $this->markTestSkipped();
        return;
        $this->create_user(true);
        midcom::get('auth')->request_sudo('midgard.admin.asgard');

        $data = $this->run_handler('net.nehmer.static', array('__mfa', 'asgard', 'trash'));
        $this->assertEquals('____mfa-asgard-trash', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_trash_type()
    {
        $this->create_user(true);
        midcom::get('auth')->request_sudo('midgard.admin.asgard');

        $data = $this->run_handler('net.nehmer.static', array('__mfa', 'asgard', 'trash', 'midgard_style'));
        $this->assertEquals('____mfa-asgard-trash_type', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }
}
?>
