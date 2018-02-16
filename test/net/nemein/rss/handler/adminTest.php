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
class net_nemein_rss_handler_adminTest extends openpsa_testcase
{
    protected static $topic;

    public static function setUpBeforeClass()
    {
        self::create_user(true);
        $data = [
            'component' => 'net.nehmer.blog',
            'name' => __CLASS__ . time()
        ];
        self::$topic = self::create_class_object(midcom_db_topic::class, $data);
        self::$topic->set_parameter('net.nehmer.blog', 'rss_subscription_enable', true);
    }

    public function test_handler_subscribe()
    {
        $data = $this->run_handler(self::$topic, ['__feeds', 'rss', 'subscribe']);
        $this->assertEquals('____feeds-rss-feeds_subscribe', $data['handler_id']);
    }

    public function test_handler_edit()
    {
        $feed = $this->create_object(net_nemein_rss_feed_dba::class, ['node' => self::$topic->id]);
        $data = $this->run_handler(self::$topic, ['__feeds', 'rss', 'edit', $feed->guid]);
        $this->assertEquals('____feeds-rss-feeds_edit', $data['handler_id']);
    }
}
