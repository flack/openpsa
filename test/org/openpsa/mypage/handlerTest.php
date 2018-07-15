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
class org_openpsa_mypage_handlerTest extends openpsa_testcase
{
    public function test_prepare_timestamps()
    {
        $topic = $this->get_component_node('org.openpsa.mypage');
        $config = midcom_baseclasses_components_configuration::get('org.openpsa.mypage', 'config');
        $viewer = new org_openpsa_mypage_viewer($topic, $config);
        $handler = new org_openpsa_mypage_handler_workingon();
        $handler->initialize($viewer, $viewer->get_router());
        $handler->prepare_timestamps(new Datetime('2011-10-26'));
        $this->assertEquals('2011-10-26', $viewer->_request_data['this_day']);
        $this->assertEquals(mktime(0, 0, 0, 10, 26, 2011), $viewer->_request_data['day_start']);
        $this->assertEquals(mktime(23, 59, 59, 10, 26, 2011), $viewer->_request_data['day_end']);
        $this->assertEquals('2011-10-25', $viewer->_request_data['prev_day']);
        $this->assertEquals('2011-10-27', $viewer->_request_data['next_day']);
        $this->assertEquals(mktime(0, 0, 0, 10, 24, 2011), $viewer->_request_data['week_start']);
        $this->assertEquals(mktime(23, 59, 59, 10, 30, 2011), $viewer->_request_data['week_end']);
    }
}
