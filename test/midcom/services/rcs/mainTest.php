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
    public function test_load_handler()
    {
        $conf = new midcom_config;
        $conf['midcom_services_rcs_root'] = '/tmp';
        $conf['midcom_services_rcs_bin_dir'] = midcom::get()->config->get('midcom_services_rcs_bin_dir');
        $conf['midcom_services_rcs_enable'] = true;

        $rcs = new midcom_services_rcs($conf);
        $topic = new midcom_db_topic;
        $handler = $rcs->load_handler($topic);
        $this->assertFalse($handler);

        $topic = $this->create_object(midcom_db_topic::class);
        $handler = $rcs->load_handler($topic);
        $this->assertInstanceOf(midcom_services_rcs_backend_rcs::class, $handler);
    }

    public function test_update()
    {
        $conf = new midcom_config;
        $conf['midcom_services_rcs_root'] = '/tmp';
        $conf['midcom_services_rcs_bin_dir'] = midcom::get()->config->get('midcom_services_rcs_bin_dir');
        $conf['midcom_services_rcs_enable'] = true;
        $topic = $this->create_object(midcom_db_topic::class);

        $rcs = new midcom_services_rcs($conf);
        $this->assertTrue($rcs->update($topic));
    }
}
