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
require_once OPENPSA_TEST_ROOT . 'org/openpsa/directmarketing/__helper/campaign.php';

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class org_openpsa_directmarketing_handler_exportTest extends openpsa_testcase
{
    protected static $_person;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);
    }

    public function testHandler_csv()
    {
        $helper = new openpsa_test_campaign_helper($this);
        $campaign = $helper->get_campaign();

        midcom::get('auth')->request_sudo('org.openpsa.directmarketing');

        $data = $this->run_handler('org.openpsa.directmarketing', array('campaign', 'export', 'csv', $campaign->guid, 'filename.csv'));
        $this->assertEquals('export_csv1', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_csv2()
    {
        $helper = new openpsa_test_campaign_helper($this);
        $campaign = $helper->get_campaign();

        midcom::get('auth')->request_sudo('org.openpsa.directmarketing');

        $url = $this->run_relocate_handler('org.openpsa.directmarketing', array('campaign', 'export', 'csv', $campaign->guid));
        $this->assertEquals('campaign/export/csv/' . $campaign->guid . '/_' . date('Y-m-d') . '.csv', $url);

        midcom::get('auth')->drop_sudo();
    }
}
?>