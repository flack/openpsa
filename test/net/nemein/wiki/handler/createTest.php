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
class net_nemein_wiki_handler_createTest extends openpsa_testcase
{
    protected static $_topic;

    public static function setUpBeforeClass()
    {
        $topic_attributes = [
            'component' => 'net.nemein.wiki',
            'name' => __CLASS__ . time()
        ];
        self::$_topic = self::create_class_object('midcom_db_topic', $topic_attributes);
    }

    public function testHandler_create()
    {
        midcom::get()->auth->request_sudo('net.nemein.wiki');

        $_GET['wikiword'] = __CLASS__ . ' ' . time();
        $data = $this->run_handler(self::$_topic, ['create']);
        $this->assertEquals('create_by_word', $data['handler_id']);

        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_create_word_by_schema()
    {
        midcom::get()->auth->request_sudo('net.nemein.wiki');

        $_GET['wikiword'] = __CLASS__ . ' ' . time();
        $data = $this->run_handler(self::$_topic, ['create', 'default']);
        $this->assertEquals('create_by_word_schema', $data['handler_id']);

        $this->show_handler($data);
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_create_word_with_namespace_by_schema()
    {
        midcom::get()->auth->request_sudo('net.nemein.wiki');

        $topic_name = __CLASS__ . time();
        $wikiword = time();
        $_GET['wikiword'] = $topic_name . ' / ' . $wikiword;
        $url = $this->run_relocate_handler(self::$_topic, ['create', 'default']);

        $qb = midcom_db_topic::new_query_builder();
        $qb->add_constraint('up', '=', self::$_topic->id);
        $qb->add_constraint('title', '=', $topic_name);
        $topics = $qb->execute();
        $this->register_objects($topics);

        $this->assertCount(1, $topics);
        $this->assertEquals('net.nemein.wiki', $topics[0]->component);

        $qb = net_nemein_wiki_wikipage::new_query_builder();
        $qb->add_constraint('topic', '=', $topics[0]->id);
        $articles = $qb->execute();
        $this->register_objects($articles);

        $this->assertCount(1, $articles);
        $this->assertEquals('index', $articles[0]->name);

        $this->assertEquals($topics[0]->name . '/create/default?wikiword=' . rawurlencode($wikiword), $url);
        midcom::get()->auth->drop_sudo();
    }
}
