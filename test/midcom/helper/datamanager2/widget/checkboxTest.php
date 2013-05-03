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
class midcom_helper_datamanager2_widget_checkboxTest extends openpsa_testcase
{
    public function test_get_default()
    {
        $dm2_helper = new openpsa_test_dm2_helper;
        $widget = $dm2_helper->get_widget('checkbox', 'boolean');

        $this->assertNull($widget->get_default(), 'nullstorage test failed');

        $dm2_helper->defaults = array('test_checkbox_1' => false);
        $widget = $dm2_helper->get_widget('checkbox', 'boolean');

        $this->assertFalse($widget->get_default(), 'nullstorage/default test failed');

        $host = new midcom_db_host;
        $dm2_helper = new openpsa_test_dm2_helper($host);
        $widget = $dm2_helper->get_widget('checkbox', 'boolean', array('storage' => 'online'));

        $this->assertNull($widget->get_default(), 'create test failed');

        $dm2_helper->defaults = array('test_checkbox_1' => false);
        $widget = $dm2_helper->get_widget('checkbox', 'boolean');

        $this->assertFalse($widget->get_default(), 'create/default test failed');

        $host = $this->create_object('midcom_db_host');
        $dm2_helper = new openpsa_test_dm2_helper($host);
        $widget = $dm2_helper->get_widget('checkbox', 'boolean', array('storage' => 'online'));

        $this->assertFalse($widget->get_default(), 'simple test failed');

        $host->online = true;
        $dm2_helper = new openpsa_test_dm2_helper($host);
        $widget = $dm2_helper->get_widget('checkbox', 'boolean', array('storage' => 'online'));

        $this->assertTrue($widget->get_default(), 'simple/storage test failed');
    }
}
?>