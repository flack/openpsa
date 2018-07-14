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
class net_nemein_rss_handler_listTest extends openpsa_testcase
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
