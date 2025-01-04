<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\services\rcs;

use openpsa_testcase;
use midcom_config;
use midcom;
use midcom_services_rcs;
use midcom_db_topic;
use midcom_services_rcs_backend_null;
use midcom_services_rcs_backend_rcs;
use midcom\events\dbaevent;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class mainTest extends openpsa_testcase
{
    private string $tmpdir;

    public function setUp() : void
    {
        $class = str_replace('\\', '', __CLASS__);
        $this->tmpdir = OPENPSA2_UNITTEST_OUTPUT_DIR . '/' . $class;
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
        $rcs->update(new dbaevent($topic));
        $this->assertCount(1, $rcs->load_backend($topic)->get_history()->all());
    }
}
