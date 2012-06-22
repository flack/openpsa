<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR);
    require_once(OPENPSA_TEST_ROOT . 'rootfile.php');
}

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_helper__componentloaderTest extends openpsa_testcase
{
    /**
     * @expectedException midcom_error
     */
    public function test_load_nonexistant()
    {
        $componentloader = new midcom_helper__componentloader();
        $componentloader->load_all_manifests();
        $componentloader->load('invalid component name');
    }

    public function test_load()
    {
        $componentloader = new midcom_helper__componentloader();
        $componentloader->load_all_manifests();
        $componentloader->load('org.openpsa.user');
    }

    public function test_load_graceful()
    {
        $componentloader = new midcom_helper__componentloader();
        $componentloader->load_all_manifests();
        $this->assertTrue($componentloader->load_graceful('org.openpsa.user'));
        $this->assertFalse($componentloader->load_graceful('nonexistant component'));
    }

    public function test_is_loaded()
    {
        $componentloader = new midcom_helper__componentloader();
        $componentloader->load_all_manifests();
        $componentloader->load('org.openpsa.user');
        $this->assertTrue($componentloader->is_loaded('org.openpsa.user'));
        $this->assertTrue($componentloader->is_loaded('midcom'));
        $this->assertFalse($componentloader->is_loaded('nonexistant component'));
    }

    public function test_is_installed()
    {
        $componentloader = new midcom_helper__componentloader();
        $componentloader->load_all_manifests();
        $this->assertTrue($componentloader->is_installed('org.openpsa.user'));
        $this->assertTrue($componentloader->is_installed('midcom'));
        $this->assertFalse($componentloader->is_installed('nonexistant component'));
    }

    public function test_get_interface_class()
    {
        $componentloader = new midcom_helper__componentloader();
        $componentloader->load_all_manifests();
        $interface = $componentloader->get_interface_class('org.openpsa.user');
        $this->assertTrue(is_a($interface, 'org_openpsa_user_interface'));
    }

    public function test_path_to_snippetpath()
    {
        $componentloader = new midcom_helper__componentloader();
        $componentloader->load_all_manifests();
        $this->assertEquals('/org/openpsa/user', $componentloader->path_to_snippetpath('org.openpsa.user'));
    }

    public function test_snippetpath_to_prefix()
    {
        $componentloader = new midcom_helper__componentloader();
        $componentloader->load_all_manifests();
        $this->assertEquals('org_openpsa_user', $componentloader->snippetpath_to_prefix('/org/openpsa/user'));
    }

    public function test_path_to_prefix()
    {
        $componentloader = new midcom_helper__componentloader();
        $componentloader->load_all_manifests();
        $this->assertEquals('org_openpsa_user', $componentloader->path_to_prefix('org.openpsa.user'));
    }

    public function test_validate_path()
    {
        $componentloader = new midcom_helper__componentloader();
        $componentloader->load_all_manifests();
        $this->assertTrue($componentloader->validate_path('/org/openpsa/user'));
        $this->assertFalse($componentloader->validate_path('/nonexistant'));
    }

    public function test_list_loaded_components()
    {
        $componentloader = new midcom_helper__componentloader();
        $componentloader->load_all_manifests();
        $this->assertEquals(array(), $componentloader->list_loaded_components());
    }
}
?>