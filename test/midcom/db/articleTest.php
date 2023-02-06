<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\db;

use openpsa_testcase;
use midcom_db_topic;
use midcom;
use midcom_db_article;
use midcom_connection;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class articleTest extends openpsa_testcase
{
    protected static midcom_db_topic $_topic;

    public static function setUpBeforeClass() : void
    {
        self::$_topic = self::create_class_object(midcom_db_topic::class);
    }

    public function testCRUD()
    {
        midcom::get()->auth->request_sudo('midcom.core');

        $article = new midcom_db_article();
        $stat = $article->create();
        $this->assertFalse($stat, midcom_connection::get_error_string());

        $article = new midcom_db_article();
        $article->_use_rcs = false;
        $article->topic = self::$_topic->id;
        $stat = $article->create();
        $this->assertTrue($stat, midcom_connection::get_error_string());

        $this->register_object($article);

        $article->title = 'test';
        $stat = $article->update();
        $this->assertTrue($stat);
        $this->assertEquals('test', $article->title);

        $stat = $article->delete();
        $this->assertTrue($stat);

        midcom::get()->auth->drop_sudo();
    }

    public function test_get_parent()
    {
        $attributes = ['topic' => self::$_topic->id];
        $article1 = $this->create_object(midcom_db_article::class, $attributes);
        $attributes['up'] = $article1->id;
        $attributes['name'] = 'test2';
        $article2 = $this->create_object(midcom_db_article::class, $attributes);
        $parent2 = $article2->get_parent();
        $this->assertEquals($parent2->guid, $article1->guid);
        $parent1 = $article1->get_parent();
        $this->assertEquals($parent1->guid, self::$_topic->guid);
    }
}
