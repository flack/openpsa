<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\services\rcs;

use PHPUnit\Framework\TestCase;
use midcom_config;
use midcom_services_rcs_config;
use midcom;
use midcom_services_rcs_backend_rcs;
use midcom_services_rcs_backend_null;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class configTest extends TestCase
{
    public function test_use_rcs()
    {
        $conf = new midcom_config;
        $conf['midcom_services_rcs_enable'] = true;

        $config = new midcom_services_rcs_config($conf);
        $this->assertTrue($config->use_rcs());

        $conf['midcom_services_rcs_enable'] = false;

        $config = new midcom_services_rcs_config($conf);
        $this->assertFalse($config->use_rcs());
    }

    public function test_get_bin_prefix()
    {
        $conf = new midcom_config;
        $conf['midcom_services_rcs_bin_dir'] = '/usr/bin';

        $config = new midcom_services_rcs_config($conf);
        $this->assertEquals('/usr/bin/', $config->get_bin_prefix());
    }

    public function test_get_backend_class()
    {
        $conf = new midcom_config;
        $conf['midcom_services_rcs_root'] = '/tmp';
        $conf['midcom_services_rcs_bin_dir'] = midcom::get()->config->get('midcom_services_rcs_bin_dir');
        $conf['midcom_services_rcs_enable'] = true;

        $config = new midcom_services_rcs_config($conf);
        $backend = $config->get_backend_class();
        $this->assertEquals(midcom_services_rcs_backend_rcs::class, $backend);

        $conf['midcom_services_rcs_enable'] = false;

        $config = new midcom_services_rcs_config($conf);
        $backend = $config->get_backend_class();
        $this->assertEquals(midcom_services_rcs_backend_null::class, $backend);
    }
}
