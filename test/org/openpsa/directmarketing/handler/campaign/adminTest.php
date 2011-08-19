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
require_once OPENPSA_TEST_ROOT . 'org/openpsa/directmarketing/__helper/campaign.php';

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class org_openpsa_directmarketing_handler_campaign_adminTest extends openpsa_testcase
{
    protected static $_person;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_edit_query()
    {
        $helper = new org_openpsa_directmarketing_campaign_helper($this);
        $campaign = $helper->get_campaign();

        midcom::get('auth')->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', array('campaign', 'edit_query', $campaign->guid));
        $this->assertEquals('edit_campaign_query', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_edit_query_advanced()
    {
        $helper = new org_openpsa_directmarketing_campaign_helper($this);
        $campaign = $helper->get_campaign();

        midcom::get('auth')->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', array('campaign', 'edit_query_advanced', $campaign->guid));
        $this->assertEquals('edit_campaign_query_advanced', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_edit()
    {
        $helper = new org_openpsa_directmarketing_campaign_helper($this);
        $campaign = $helper->get_campaign();

        midcom::get('auth')->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', array('campaign', 'edit', $campaign->guid));
        $this->assertEquals('edit_campaign', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_delete()
    {
        $helper = new org_openpsa_directmarketing_campaign_helper($this);
        $campaign = $helper->get_campaign();

        midcom::get('auth')->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', array('campaign', 'delete', $campaign->guid));
        $this->assertEquals('delete_campaign', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }
}
?>