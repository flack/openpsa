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
class net_nemein_wiki_handler_viewTest extends openpsa_testcase
{
    protected static $_topic;
    protected static $_page;

    public static function setUpBeforeClass()
    {
        $topic_attributes = [
            'component' => 'net.nemein.wiki',
            'name' => __CLASS__ . time(),
            'extra' => __CLASS__ . time()
        ];
        self::$_topic = self::create_class_object('midcom_db_topic', $topic_attributes);
        $article_properties = [
            'topic' => self::$_topic->id,
            'title' => __CLASS__ . ' ' . time(),
            'content' => midcom::get()->i18n->get_l10n('net.nemein.wiki')->get('wiki default page content')
        ];
        self::$_page = self::create_class_object('net_nemein_wiki_wikipage', $article_properties);
    }

    public function testHandler_start()
    {
        midcom::get()->auth->request_sudo('net.nemein.wiki');

        $data = $this->run_handler(self::$_topic);
        $this->assertEquals('start', $data['handler_id']);

        $qb = net_nemein_wiki_wikipage::new_query_builder();
        $qb->add_constraint('topic', '=', self::$_topic->id);
        $qb->add_constraint('name', '=', 'index');
        $results = $qb->execute();
        $this->register_objects($results);
        $this->assertEquals(1, sizeof($results));
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_view()
    {
        $data = $this->run_handler(self::$_topic, [self::$_page->name]);
        $this->assertEquals('view', $data['handler_id']);
        $this->show_handler($data);
    }

    public function testHandler_raw()
    {
        $data = $this->run_handler(self::$_topic, ['raw', self::$_page->name]);
        $this->assertEquals('raw', $data['handler_id']);
        $this->show_handler($data);
    }

    public function testHandler_whatlinks()
    {
        $data = $this->run_handler(self::$_topic, ['whatlinks', self::$_page->name]);
        $this->assertEquals('whatlinks', $data['handler_id']);
        $this->show_handler($data);
    }

    public function testHandler_subscribe()
    {
        self::create_user(true);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['subscribe'] = true;

        $url = $this->run_relocate_handler(self::$_topic, ['subscribe', self::$_page->name]);
        $this->assertEquals(self::$_page->name . '/', $url);
    }
}
