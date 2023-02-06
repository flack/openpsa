<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\net\nemein\wiki\handler;

use openpsa_testcase;
use midcom_db_topic;
use midcom;
use net_nemein_wiki_wikipage;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class viewTest extends openpsa_testcase
{
    protected static midcom_db_topic $_topic;
    protected static net_nemein_wiki_wikipage $_page;

    public static function setUpBeforeClass() : void
    {
        $topic_attributes = [
            'component' => 'net.nemein.wiki',
            'name' => \midcom_helper_misc::urlize(__CLASS__) . time(),
            'extra' => __CLASS__ . time()
        ];
        self::$_topic = self::create_class_object(midcom_db_topic::class, $topic_attributes);
        $article_properties = [
            'topic' => self::$_topic->id,
            'title' => __CLASS__ . ' ' . time(),
            'content' => midcom::get()->i18n->get_string('wiki default page content', 'net.nemein.wiki')
        ];
        self::$_page = self::create_class_object(net_nemein_wiki_wikipage::class, $article_properties);
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
        $this->assertCount(1, $results);
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_view()
    {
        $data = $this->run_handler(self::$_topic, [self::$_page->name]);
        $this->assertEquals('view', $data['handler_id']);
    }

    public function testHandler_raw()
    {
        $data = $this->run_handler(self::$_topic, ['raw', self::$_page->name]);
        $this->assertEquals('raw', $data['handler_id']);
    }

    public function testHandler_whatlinks()
    {
        $data = $this->run_handler(self::$_topic, ['whatlinks', self::$_page->name]);
        $this->assertEquals('whatlinks', $data['handler_id']);
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
