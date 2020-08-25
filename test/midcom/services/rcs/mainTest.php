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
class midcom_services_rcs_mainTest extends openpsa_testcase
{
    private $tmpdir;

    public function setUp() : void
    {
        $this->tmpdir = OPENPSA2_UNITTEST_OUTPUT_DIR . '/' . __CLASS__;
        if (!file_exists($this->tmpdir)) {
            mkdir($this->tmpdir);
        }
    }

    public function test_load_backend()
    {
        $conf = new midcom_config;
        $conf['midcom_services_rcs_root'] = $this->tmpdir;
        $conf['midcom_services_rcs_bin_dir'] = midcom::get()->config->get('midcom_services_rcs_bin_dir');
        $conf['midcom_services_rcs_enable'] = true;

        $rcs = new midcom_services_rcs($conf);
        $topic = new midcom_db_topic;
        $handler = $rcs->load_backend($topic);
        $this->assertInstanceOf(midcom_services_rcs_backend_null::class, $handler);

        $topic = $this->create_object(midcom_db_topic::class);
        $handler = $rcs->load_backend($topic);
        $this->assertInstanceOf(midcom_services_rcs_backend_rcs::class, $handler);
    }

    public function test_update()
    {
        $conf = new midcom_config;
        $conf['midcom_services_rcs_root'] = $this->tmpdir;
        $conf['midcom_services_rcs_bin_dir'] = midcom::get()->config->get('midcom_services_rcs_bin_dir');
        $conf['midcom_services_rcs_enable'] = true;
        $topic = $this->create_object(midcom_db_topic::class);

        $rcs = new midcom_services_rcs($conf);
        $this->assertTrue($rcs->update($topic));
    }
}
