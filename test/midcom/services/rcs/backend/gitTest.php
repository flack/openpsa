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
class midcom_services_rcs_backend_gitTest extends openpsa_testcase
{
    public function test_get_history()
    {
        $tmpdir = OPENPSA2_UNITTEST_OUTPUT_DIR . '/' . __CLASS__;
        if (!file_exists($tmpdir)) {
            mkdir($tmpdir);
        }

        $config = new midcom_config;
        $config->set('midcom_services_rcs_enable', true);
        $config->set('midcom_services_rcs_root', $tmpdir);
        $config = new midcom_services_rcs_config($config);

        $topic = $this->create_object(midcom_db_topic::class, ['_use_rcs' => false]);
        $backend = new midcom_services_rcs_backend_git($topic, $config);

        $this->assertEquals([], $backend->get_history()->all());
        $topic->title = 'TEST';
        $backend->update();
        $backend = new midcom_services_rcs_backend_git($topic, $config);

        $this->assertCount(1, $backend->get_history()->all());
    }
}
