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
class midcom_admin_help_helpTest extends openpsa_testcase
{
    /**
     * @expectedException midcom_error
     */
    public function test_check_component()
    {
        midcom_admin_help_help::check_component('midcom');
        midcom_admin_help_help::check_component(null);
        midcom_admin_help_help::check_component('non.existant');
    }

    public function test_help_exists()
    {
        $stat = midcom_admin_help_help::help_exists('nonexistant', 'org.openpsa.expenses');
        $this->assertFalse($stat);
        $stat = midcom_admin_help_help::help_exists('handlers_view', 'net.nehmer.blog');
        $this->assertTrue($stat);
    }

    public function test_get_documentation_dir()
    {
        $path = midcom_admin_help_help::get_documentation_dir('midcom');
        $this->assertEquals(MIDCOM_ROOT . '/midcom/documentation/', $path);

        $path = midcom_admin_help_help::get_documentation_dir('org.openpsa.core');
        $this->assertEquals(MIDCOM_ROOT . '/org/openpsa/core/documentation/', $path);
    }

    public function test_generate_file_path()
    {
        $path = midcom_admin_help_help::generate_file_path('handlers_view', 'net.nehmer.blog', 'en');
        $this->assertEquals(MIDCOM_ROOT . '/net/nehmer/blog/documentation/handlers_view.en.txt', $path);

        $path = midcom_admin_help_help::generate_file_path('handlers_view', 'net.nehmer.blog');
        $this->assertEquals(MIDCOM_ROOT . '/net/nehmer/blog/documentation/handlers_view.en.txt', $path);

        $path = midcom_admin_help_help::generate_file_path('handlers_view', 'net.nehmer.blog', 'xx');
        $this->assertEquals(MIDCOM_ROOT . '/net/nehmer/blog/documentation/handlers_view.en.txt', $path);

        $path = midcom_admin_help_help::generate_file_path('handlers_nonexistant', 'net.nehmer.blog', 'xx');
        $this->assertNull($path);
    }

    public function test_get_help_title()
    {
        $stat = midcom_admin_help_help::get_help_title('help_style', 'org.openpsa.relatedto');
        $this->assertNotEquals('help_style', $stat);
    }


}
?>