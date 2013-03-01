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
class net_nemein_wiki_handler_deleteTest extends openpsa_testcase
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

    public function testHandler_delete()
    {
        midcom::get('auth')->request_sudo('net.nemein.wiki');

        $data = $this->run_handler(self::$_topic, array('delete', self::$_page->name));
        $this->assertEquals('delete', $data['handler_id']);

        $this->show_handler($data);
        midcom::get('auth')->drop_sudo();
    }
}
?>