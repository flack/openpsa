<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\net\nemein\redirector\handler;

use openpsa_testcase;
use midcom;
use midcom_db_topic;
use net_nemein_redirector_tinyurl_dba;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class redirectTest extends openpsa_testcase
{
    protected static $_topic;

    public static function setUpBeforeClass() : void
    {
        $topic_attributes = [
            'component' => 'net.nemein.redirector',
            'name' => __CLASS__ . time()
        ];
        self::$_topic = self::create_class_object(midcom_db_topic::class, $topic_attributes);
    }

    public function testHandler_redirect()
    {
        midcom::get()->auth->request_sudo('net.nemein.redirector');
        $attributes = [
            'node' => self::$_topic->guid,
            'name' => net_nemein_redirector_tinyurl_dba::generate(),
            'url' => 'test'
        ];
        $tinyurl = $this->create_object(net_nemein_redirector_tinyurl_dba::class, $attributes);

        $url = $this->run_relocate_handler(self::$_topic, [$tinyurl->name]);
        $this->assertEquals('test', $url);

        midcom::get()->auth->drop_sudo();
    }
}
