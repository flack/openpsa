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
    require_once(OPENPSA_TEST_ROOT . 'rootfile.php');
}

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_services_rcs_configTest extends openpsa_testcase
{
    public function test_use_rcs()
    {
        $args = array
        (
            'midcom_services_rcs_enable' => true
        );

        $config = new midcom_services_rcs_config($args);
        $this->assertTrue($config->use_rcs());

        $args = array
        (
            'midcom_services_rcs_enable' => false
        );

        $config = new midcom_services_rcs_config($args);
        $this->assertFalse($config->use_rcs());

        $args = array();

        $config = new midcom_services_rcs_config($args);
        $this->assertFalse($config->use_rcs());
    }

    public function test_get_bin_prefix()
    {
        $args = array
        (
            'midcom_services_rcs_bin_dir' => '/usr/bin'
        );

        $config = new midcom_services_rcs_config($args);
        $this->assertEquals('/usr/bin', $config->get_bin_prefix());
    }

    public function test_get_handler()
    {
        $args = array
        (
            'midcom_services_rcs_root' => '/tmp',
            'midcom_services_rcs_bin_dir' => '/usr/bin',
            'midcom_services_rcs_enable' => true
        );

        $topic = new midcom_db_topic;

        $config = new midcom_services_rcs_config($args);
        $handler = $config->get_handler($topic);
        $this->assertEquals('midcom_services_rcs_backend_rcs', get_class($handler));
    }
}
?>