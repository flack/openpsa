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
class deleteTest extends openpsa_testcase
{
    protected static midcom_db_topic $_topic;
    protected static net_nemein_wiki_wikipage $_page;

    public static function setUpBeforeClass() : void
    {
        $topic_attributes = [
            'component' => 'net.nemein.wiki',
            'name' => \midcom_helper_misc::urlize(__CLASS__) . time()
        ];
        self::$_topic = self::create_class_object(midcom_db_topic::class, $topic_attributes);
        $article_properties = [
            'topic' => self::$_topic->id,
            'title' => __CLASS__ . ' ' . time()
        ];
        self::$_page = self::create_class_object(net_nemein_wiki_wikipage::class, $article_properties);
    }

    public function testHandler_delete()
    {
        midcom::get()->auth->request_sudo('net.nemein.wiki');

        $url = $this->run_relocate_handler(self::$_topic, ['delete', self::$_page->name]);
        $this->assertEquals('', $url);

        midcom::get()->auth->drop_sudo();
    }
}
