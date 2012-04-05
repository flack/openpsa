<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(dirname(dirname(__FILE__))))) . DIRECTORY_SEPARATOR);
    require_once OPENPSA_TEST_ROOT . 'rootfile.php';
}

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class net_nemein_wiki_handler_viewTest extends openpsa_testcase
{
    protected static $_topic;
    protected static $_page;

    public static function setUpBeforeClass()
    {
        $topic_attributes = array
        (
            'component' => 'net.nemein.wiki',
            'name' => __CLASS__ . time()
        );
        self::$_topic = self::create_class_object('midcom_db_topic', $topic_attributes);
        $article_properties = array
        (
            'topic' => self::$_topic->id,
            'title' => __CLASS__ . ' ' . time()
        );
        self::$_page = self::create_class_object('net_nemein_wiki_wikipage', $article_properties);
    }

    public function testHandler_start()
    {
        midcom::get('auth')->request_sudo('net.nemein.wiki');

        $url = $this->run_relocate_handler(self::$_topic);
        //the anchor prefix is not yet available when the relocate is triggered, so the
        //URL will look differently
        $this->assertEquals(midcom::get()->get_page_prefix() . 'notfound/index/', $url);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_view()
    {
        $data = $this->run_handler(self::$_topic, array(self::$_page->name));
        $this->assertEquals('view', $data['handler_id']);
        $this->show_handler($data);
    }

    public function testHandler_raw()
    {
        $data = $this->run_handler(self::$_topic, array('raw', self::$_page->name));
        $this->assertEquals('raw', $data['handler_id']);
        $this->show_handler($data);
    }

    public function testHandler_source()
    {
        $data = $this->run_handler(self::$_topic, array('source', self::$_page->name));
        $this->assertEquals('source', $data['handler_id']);
        $this->show_handler($data);
    }

    public function testHandler_whatlinks()
    {
        $data = $this->run_handler(self::$_topic, array('whatlinks', self::$_page->name));
        $this->assertEquals('whatlinks', $data['handler_id']);
        $this->show_handler($data);
    }

    public function testHandler_subscribe()
    {
        self::create_user(true);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['subscribe'] = true;

        $url = $this->run_relocate_handler(self::$_topic, array('subscribe', self::$_page->name));
        $this->assertEquals(self::$_page->name . '/', $url);
    }
}
?>