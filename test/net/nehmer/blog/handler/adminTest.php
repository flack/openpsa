<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\net\nehmer\blog\handler;

use openpsa_testcase;
use midcom_db_article;
use midcom;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class adminTest extends openpsa_testcase
{
    protected static $_topic;
    protected static $_article;

    public static function setUpBeforeClass() : void
    {
        self::$_topic = self::get_component_node('net.nehmer.blog');

        $article_properties = [
            'topic' => self::$_topic->id,
            'name' => \midcom_helper_misc::urlize(__CLASS__) . time()
        ];
        self::$_article = self::create_class_object(midcom_db_article::class, $article_properties);
    }

    public function testHandler_edit()
    {
        midcom::get()->auth->request_sudo('net.nehmer.blog');

        $data = $this->run_handler(self::$_topic, ['edit', self::$_article->guid]);
        $this->assertEquals('edit', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_delete()
    {
        midcom::get()->auth->request_sudo('net.nehmer.blog');

        $_POST = ['referrer' => self::$_article->name . '/'];
        $url = $this->run_relocate_handler(self::$_topic, ['delete', self::$_article->guid]);
        $this->assertEquals(self::$_article->name . '/', $url);

        midcom::get()->auth->drop_sudo();
    }
}
