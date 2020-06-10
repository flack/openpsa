<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use PHPUnit\Framework\TestCase;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_helper__componentloaderTest extends TestCase
{
    public function test_is_installed()
    {
        $componentloader = new midcom_helper__componentloader(['org.openpsa.user' => '', 'midcom' => '']);
        $this->assertTrue($componentloader->is_installed('org.openpsa.user'));
        $this->assertTrue($componentloader->is_installed('midcom'));
        $this->assertFalse($componentloader->is_installed('nonexistent component'));
    }

    public function test_get_interface_class()
    {
        $componentloader = new midcom_helper__componentloader(['org.openpsa.user' => '']);
        $interface = $componentloader->get_interface_class('org.openpsa.user');
        $this->assertInstanceOf(org_openpsa_user_interface::class, $interface);
    }

    public function test_path_to_snippetpath()
    {
        $componentloader = new midcom_helper__componentloader(['org.openpsa.user' => MIDCOM_ROOT . '/org/openpsa/user/config/manifest.inc']);
        $this->assertEquals(MIDCOM_ROOT . '/org/openpsa/user', $componentloader->path_to_snippetpath('org.openpsa.user'));
        $this->assertFalse($componentloader->path_to_snippetpath('non.existent.component'));
    }

    public function test_path_to_prefix()
    {
        $componentloader = new midcom_helper__componentloader([]);
        $this->assertEquals('org_openpsa_user', $componentloader->path_to_prefix('org.openpsa.user'));
    }
}
