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
class net_nemein_rss_handler_fetchTest extends openpsa_testcase
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

    public function test_handler_fetch()
    {
        $data = $this->run_handler(self::$topic, ['__feeds', 'rss', 'fetch', 'all']);
        $this->assertEquals('____feeds-rss-feeds_fetch_all', $data['handler_id']);
        $this->show_handler($data);
    }
}