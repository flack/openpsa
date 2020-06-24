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
class midcom_services_rcs_backend_rcsTest extends openpsa_testcase
{
    public function test_list_history()
    {
        $config = new midcom_config;
        $config->set('midcom_services_rcs_enable', true);
        $config = new midcom_services_rcs_config($config);

        $topic = $this->create_object(midcom_db_topic::class, ['_use_rcs' => false]);
        $backend = new midcom_services_rcs_backend_rcs($topic, $config);

        $this->assertEquals([], $backend->list_history());
        $topic->_use_rcs = true;
        $topic->title = 'TEST';
        midcom::get()->auth->request_sudo('midcom.core');
        $this->assertTrue($topic->update(), midcom_connection::get_error_string());
        midcom::get()->auth->drop_sudo();
        $backend = new midcom_services_rcs_backend_rcs($topic, $config);

        $this->assertCount(1, $backend->list_history());
    }
}
