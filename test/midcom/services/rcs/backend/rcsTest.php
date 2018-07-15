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
    protected static $_config;

    public static function setUpBeforeClass()
    {
        $conf = new midcom_config;
        $conf['midcom_services_rcs_enable'] = true;
        midcom::get()->config->set('midcom_services_rcs_enable', true);

        self::$_config = new midcom_services_rcs_config($conf);
    }

    public function test_list_history()
    {
        $topic = $this->create_object(midcom_db_topic::class, ['_use_rcs' => false]);
        $handler = self::$_config->get_handler($topic);

        $this->assertEquals([], $handler->list_history());
        $topic->_use_rcs = true;
        $topic->title = 'TEST';
        midcom::get()->auth->request_sudo('midcom.core');
        $this->assertTrue($topic->update(), midcom_connection::get_error_string());
        midcom::get()->auth->drop_sudo();
        $handler = self::$_config->get_handler($topic);

        $this->assertCount(1, $handler->list_history());
    }
}
