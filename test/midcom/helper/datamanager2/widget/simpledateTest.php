<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

require_once OPENPSA_TEST_ROOT . 'midcom/helper/datamanager2/__helper/dm2.php';

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_helper_datamanager2_widget_simpledateTest extends openpsa_testcase
{
    public function test_get_default()
    {
        $timestamp = strtotime('2011-10-15 00:00:00');
        $default_values = array
        (
            'test_simpledate_1' => array
            (
                'd' => 15,
                'm' => 10,
                'Y' => 2011
            )
        );
        $empty_values = array
        (
            'test_simpledate_0' => array
            (
                'd' => null,
                'm' => null,
                'Y' => null
            )
        );

        $dm2_helper = new openpsa_test_dm2_helper;
        $widget = $dm2_helper->get_widget('simpledate', 'date');

        $this->assertNull($widget->get_default(), 'nullstorage test failed');

        $dm2_helper->defaults = array('test_simpledate_1' => '2011-10-15 00:00:00');
        $widget = $dm2_helper->get_widget('simpledate', 'date');

        $this->assertEquals($default_values, $widget->get_default(), 'nullstorage/default test failed');

        $event = new midcom_db_event;
        $dm2_helper = new openpsa_test_dm2_helper($event);
        $widget = $dm2_helper->get_widget('simpledate', 'date', array('storage' => 'start'));

        $this->assertNull($widget->get_default(), 'create test failed');

        $dm2_helper->defaults = array('test_simpledate_1' => '2011-10-15 00:00:00');
        $widget = $dm2_helper->get_widget('simpledate', 'date');

        $this->assertEquals($default_values, $widget->get_default(), 'create/default test failed');

        $event = $this->create_object('midcom_db_event');
        $dm2_helper = new openpsa_test_dm2_helper($event);
        $widget = $dm2_helper->get_widget('simpledate', 'date', array('storage' => 'start'));

        $this->assertEquals($empty_values, $widget->get_default(), 'simple test failed');
        $event->start = $timestamp;

        $dm2_helper = new openpsa_test_dm2_helper($event);
        //Lazy workaround to reuse the default array from above
        $dm2_helper->get_widget('simpledate', 'date', array('storage' => 'start'));
        $widget = $dm2_helper->get_widget('simpledate', 'date', array('storage' => 'start'));

        $this->assertEquals($default_values, $widget->get_default(), 'simple/storage test failed');
    }
}
?>