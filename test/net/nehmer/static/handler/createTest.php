<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\net\nehmer\handler;

use openpsa_testcase;
use midcom;
use midcom_db_topic;
use midcom_db_article;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class createTest extends openpsa_testcase
{
    protected static $_topic;
    protected static $_article;

    public static function setUpBeforeClass() : void
    {
        $topic_attributes = [
            'component' => 'net.nehmer.static',
            'name' => \midcom_helper_misc::urlize(__CLASS__) . time()
        ];
        self::$_topic = self::create_class_object(midcom_db_topic::class, $topic_attributes);
        $article_properties = [
            'topic' => self::$_topic->id,
            'name' => \midcom_helper_misc::urlize(__CLASS__) . time()
        ];
        self::$_article = self::create_class_object(midcom_db_article::class, $article_properties);
    }

    public function testHandler_create()
    {
        midcom::get()->auth->request_sudo('net.nehmer.static');

        $data = $this->run_handler(self::$_topic, ['create', 'default']);
        $this->assertEquals('create', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_createindex()
    {
        midcom::get()->auth->request_sudo('net.nehmer.static');

        $data = $this->run_handler(self::$_topic, ['createindex', 'default']);
        $this->assertEquals('createindex', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
