<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\services\rcs\backend;

use openpsa_testcase;
use midcom_config;
use midcom_services_rcs_config;
use midcom_db_topic;
use midcom_services_rcs_backend_rcs;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class rcsTest extends openpsa_testcase
{
    public function test_get_history()
    {
        $class = str_replace('\\', '', __CLASS__);
        $tmpdir = OPENPSA2_UNITTEST_OUTPUT_DIR . '/' . $class;
        if (!file_exists($tmpdir)) {
            mkdir($tmpdir);
        }

        $config = new midcom_config;
        $config->set('midcom_services_rcs_enable', true);
        $config->set('midcom_services_rcs_root', $tmpdir);
        $config = new midcom_services_rcs_config($config);

        $topic = $this->create_object(midcom_db_topic::class, ['_use_rcs' => false]);
        $backend = new midcom_services_rcs_backend_rcs($topic, $config);
        $this->assertEquals([], $backend->get_history()->all());
        $topic->_use_rcs = true;
        $topic->title = 'TEST';
        $backend->update('NOBODY');
        $backend = new midcom_services_rcs_backend_rcs($topic, $config);

        $this->assertCount(1, $backend->get_history()->all());
    }
}
