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
class net_nehmer_blog_handler_feedTest extends openpsa_testcase
{
    protected static $topic;
    protected static $article;

    public static function setUpBeforeClass()
    {
        self::$topic = self::get_component_node('net.nehmer.blog');

        $article_properties = [
            'topic' => self::$topic->id,
            'name' => __CLASS__ . time()
        ];
        self::$article = self::create_class_object(midcom_db_article::class, $article_properties);
    }

    public function testHandler_index()
    {
        $data = $this->run_handler(self::$topic, ['feeds']);
        $this->assertEquals('feed-index', $data['handler_id']);

        $this->show_handler($data);
    }

    public function testHandler_feed()
    {
        $data = $this->run_handler(self::$topic, ['feeds', 'category', 'dummy']);
        $this->assertEquals('feed-category-rss2', $data['handler_id']);

        $data = $this->run_handler(self::$topic, ['rss.xml']);
        $this->assertEquals('feed-rss2', $data['handler_id']);

        $this->show_handler($data);
    }
}
