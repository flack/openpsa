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
    require_once OPENPSA_TEST_ROOT . 'rootfile.php';
}

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class org_openpsa_mypage_viewerTest extends openpsa_testcase
{
    public function test_calculate_day()
    {
        $topic = $this->get_component_node('org.openpsa.mypage');
        $config = midcom_baseclasses_components_configuration::get('org.openpsa.mypage', 'config');
        $viewer = new org_openpsa_mypage_viewer($topic, $config);
        $viewer->calculate_day('2011-10-26');
        $this->assertEquals('2011-10-26', $viewer->_request_data['this_day']);
        $this->assertEquals(1319580000, $viewer->_request_data['day_start']);
        $this->assertEquals(1319666399, $viewer->_request_data['day_end']);
        $this->assertEquals('2011-10-25', $viewer->_request_data['prev_day']);
        $this->assertEquals('2011-10-27', $viewer->_request_data['next_day']);
        $this->assertEquals(1319407200, $viewer->_request_data['week_start']);
        $this->assertEquals(1320015599, $viewer->_request_data['week_end']);
    }
}
?>