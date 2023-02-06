<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\net\nemein\rss\handler;

use openpsa_testcase;
use midcom_db_topic;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class listTest extends openpsa_testcase
{
    protected static midcom_db_topic $topic;

    public static function setUpBeforeClass() : void
    {
        self::create_user(true);
        $data = [
            'component' => 'net.nehmer.blog',
            'name' => \midcom_helper_misc::urlize(__CLASS__) . time()
        ];
        self::$topic = self::create_class_object(midcom_db_topic::class, $data);
        self::$topic->set_parameter('net.nehmer.blog', 'rss_subscription_enable', true);
    }

    public function test_handler_opml()
    {
        $data = $this->run_handler(self::$topic, ['__feeds', 'rss', 'feeds.opml']);
        $this->assertEquals('feeds_opml', $data['handler_id']);
    }

    public function test_handler_edit()
    {
        $data = $this->run_handler(self::$topic, ['__feeds', 'rss', 'list']);
        $this->assertEquals('feeds_list', $data['handler_id']);
    }
}
