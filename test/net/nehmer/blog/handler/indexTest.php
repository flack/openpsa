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
class net_nehmer_blog_handler_indexTest extends openpsa_testcase
{
    protected static $_topic;
    protected static $_article;

    public static function setUpBeforeClass() : void
    {
        self::$_topic = self::get_component_node('net.nehmer.blog');

        $article_properties = [
            'topic' => self::$_topic->id,
            'name' => 'index'
        ];
        self::$_article = self::create_class_object(midcom_db_article::class, $article_properties);
    }

    public function testHandler_index()
    {
        midcom::get()->auth->request_sudo('net.nehmer.blog');

        $data = $this->run_handler(self::$_topic);
        $this->assertEquals('index', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_index_category()
    {
        midcom::get()->auth->request_sudo('net.nehmer.blog');

        $data = $this->run_handler(self::$_topic, ['category', 'dummy']);
        $this->assertEquals('index-category', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_latest()
    {
        midcom::get()->auth->request_sudo('net.nehmer.blog');

        $data = $this->run_handler(self::$_topic, ['latest', '2']);
        $this->assertEquals('latest', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_ajax_latest()
    {
        midcom::get()->auth->request_sudo('net.nehmer.blog');

        $data = $this->run_handler(self::$_topic, ['ajax', 'latest', '2']);
        $this->assertEquals('ajax-latest', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_latest_category()
    {
        midcom::get()->auth->request_sudo('net.nehmer.blog');

        $data = $this->run_handler(self::$_topic, ['category', 'latest', 'dummy', '2']);
        $this->assertEquals('latest-category', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
