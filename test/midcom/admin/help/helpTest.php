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
        midcom_admin_help_help::check_component('non.existent');
    }

    public function test_help_exists()
    {
        $stat = midcom_admin_help_help::help_exists('nonexistent', 'org.openpsa.expenses');
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

        $path = midcom_admin_help_help::generate_file_path('handlers_nonexistent', 'net.nehmer.blog', 'xx');
        $this->assertNull($path);
    }

    public function test_get_help_title()
    {
        $stat = midcom_admin_help_help::get_help_title('help_style', 'org.openpsa.relatedto');
        $this->assertNotEquals('help_style', $stat);
    }

    /**
     * @dataProvider provider_list_files
     */
    public function test_list_files($component, $index, $expected)
    {
        //@todo: To test properly, we should setup a context with a handler of the component
        $handler = new midcom_admin_help_help;
        $this->assertEquals($expected, $handler->list_files($component, $index));
    }

    public function provider_list_files()
    {
        return array(
            array(
                'org.openpsa.core',
                false,
                array(
                    'urlmethods' => array(
                        'path' => '/urlmethods',
                        'subject' => 'Additional URL methods',
                        'lang' => 'en'
                    ),
                )
            ),
            array(
                'net.nehmer.blog',
                false,
                array(
                    '01_component_config' => array(
                        'path' => MIDCOM_ROOT . '/net/nehmer/blog/documentation/01_component_config.en.txt',
                        'subject' => 'Component configuration',
                        'lang' => 'en'
                    ),
                    'style' => array(
                        'path' => MIDCOM_ROOT . '/net/nehmer/blog/documentation/style.en.txt',
                        'subject' => 'net.nehmer.blog style elements',
                        'lang' => 'en'
                    ),
                )
            )
        );
    }
}
