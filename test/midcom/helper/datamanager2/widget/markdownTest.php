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
class midcom_helper_datamanager2_widget_markdownTest extends openpsa_testcase
{
    public function test_get_default()
    {
        $dm2_helper = new openpsa_test_dm2_helper;
        $widget = $dm2_helper->get_widget('markdown', 'text');

        $this->assertNull($widget->get_default(), 'nullstorage test failed');

        $dm2_helper->defaults = ['test_markdown_1' => 'TEST'];
        $widget = $dm2_helper->get_widget('markdown', 'text');

        $this->assertEquals('TEST', $widget->get_default(), 'nullstorage/default test failed');

        $topic = new midcom_db_topic;
        $dm2_helper = new openpsa_test_dm2_helper($topic);
        $widget = $dm2_helper->get_widget('markdown', 'text', ['storage' => 'description']);

        $this->assertNull($widget->get_default(), 'create test failed');

        $dm2_helper->defaults = ['test_markdown_1' => 'TEST'];
        $widget = $dm2_helper->get_widget('markdown', 'text');

        $this->assertEquals('TEST', $widget->get_default(), 'create/default test failed');

        $topic = $this->create_object('midcom_db_topic');
        $dm2_helper = new openpsa_test_dm2_helper($topic);
        $widget = $dm2_helper->get_widget('markdown', 'text', ['storage' => 'description']);

        $this->assertEquals('', $widget->get_default(), 'simple test failed');
        $topic->description = 'TEST';

        $dm2_helper = new openpsa_test_dm2_helper($topic);
        //Lazy workaround to reuse the default array from above
        $dm2_helper->get_widget('markdown', 'text', ['storage' => 'description']);
        $widget = $dm2_helper->get_widget('markdown', 'text', ['storage' => 'description']);

        $this->assertEquals('TEST', $widget->get_default(), 'simple/storage test failed');
    }
}
