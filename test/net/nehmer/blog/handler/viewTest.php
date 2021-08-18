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
class viewTest extends openpsa_testcase
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

    public function testHandler_view()
    {
        midcom::get()->auth->request_sudo('net.nehmer.blog');

        $data = $this->run_handler(self::$_topic, ['index']);
        $this->assertEquals('view', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
