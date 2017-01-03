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
class midcom_helper_datamanager2_widget_jsdateTest extends openpsa_testcase
{
    public function test_get_default()
    {
        $timestamp = strtotime('2011-10-15 00:00:00');
        $default_values = array(
            'test_jsdate_1_date' => "2011-10-15",
            'test_jsdate_1_hours' => "00",
            'test_jsdate_1_minutes' => "00"
        );
        $empty_values = array(
            'test_jsdate_0_date' => "0000-00-00",
            'test_jsdate_0_hours' => "00",
            'test_jsdate_0_minutes' => "00"
        );

        $dm2_helper = new openpsa_test_dm2_helper;
        $widget = $dm2_helper->get_widget('jsdate', 'date');

        $this->assertNull($widget->get_default(), 'nullstorage test failed');

        $dm2_helper->defaults = array('test_jsdate_1' => '2011-10-15 00:00:00');
        $widget = $dm2_helper->get_widget('jsdate', 'date');

        $this->assertEquals($default_values, $widget->get_default(), 'nullstorage/default test failed');

        $event = new org_openpsa_calendar_event_dba;
        $dm2_helper = new openpsa_test_dm2_helper($event);
        $widget = $dm2_helper->get_widget('jsdate', 'date', array('storage' => 'start'));

        $this->assertNull($widget->get_default(), 'create test failed');

        $dm2_helper->defaults = array('test_jsdate_1' => '2011-10-15 00:00:00');
        $widget = $dm2_helper->get_widget('jsdate', 'date');

        $this->assertEquals($default_values, $widget->get_default(), 'create/default test failed');

        $event = $this->create_object('org_openpsa_calendar_event_dba');
        $dm2_helper = new openpsa_test_dm2_helper($event);
        $widget = $dm2_helper->get_widget('jsdate', 'date', array('storage' => 'start'));

        $this->assertEquals($empty_values, $widget->get_default(), 'simple test failed');
        $event->start = $timestamp;

        $dm2_helper = new openpsa_test_dm2_helper($event);
        //Lazy workaround to reuse the default array from above
        $dm2_helper->get_widget('jsdate', 'date', array('storage' => 'start'));
        $widget = $dm2_helper->get_widget('jsdate', 'date', array('storage' => 'start'));

        $this->assertEquals($default_values, $widget->get_default(), 'simple/storage test failed');
    }
}
