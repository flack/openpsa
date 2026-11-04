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
use midcom_db_article;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class feedTest extends openpsa_testcase
{
    protected static midcom_db_topic $topic;
    protected static midcom_db_article $article;

    public static function setUpBeforeClass() : void
    {
        self::$topic = self::get_component_node('net.nehmer.blog');

        $article_properties = [
            'topic' => self::$topic->id,
            'name' => \midcom_helper_misc::urlize(__CLASS__) . time()
        ];
        self::$article = self::create_class_object(midcom_db_article::class, $article_properties);
    }

    public function testHandler_index()
    {
        $data = $this->run_handler(self::$topic, ['feeds']);
        $this->assertEquals('feed-index', $data['handler_id']);
    }

    public function testHandler_feed()
    {
        $data = $this->run_handler(self::$topic, ['feeds', 'category', 'dummy']);
        $this->assertEquals('feed-category-rss2', $data['handler_id']);

        $data = $this->run_handler(self::$topic, 'rss.xml');
        $this->assertEquals('feed-rss2', $data['handler_id']);
    }

    public function testHandler_feed_formats()
    {
        $data = $this->run_handler(self::$topic, 'rss1.xml');
        $this->assertEquals('feed-rss1', $data['handler_id']);

        $data = $this->run_handler(self::$topic, 'rss091.xml');
        $this->assertEquals('feed-rss091', $data['handler_id']);

        $data = $this->run_handler(self::$topic, 'atom.xml');
        $this->assertEquals('feed-atom', $data['handler_id']);
    }
}
