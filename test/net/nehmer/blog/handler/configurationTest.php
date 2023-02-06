<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\net\nehmer\blog\handler;

use midcom_db_topic;
use openpsa_testcase;
use midcom;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class configurationTest extends openpsa_testcase
{
    protected static midcom_db_topic $_topic;

    public static function setUpBeforeClass() : void
    {
        self::$_topic = self::get_component_node('net.nehmer.blog');
    }

    public function testHandler_config()
    {
        midcom::get()->auth->request_sudo('net.nehmer.blog');

        $data = $this->run_handler(self::$_topic, ['config']);
        $this->assertEquals('config', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_recreate()
    {
        midcom::get()->auth->request_sudo('net.nehmer.blog');

        $data = $this->run_handler(self::$_topic, ['config', 'recreate']);
        $this->assertEquals('config_recreate', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
