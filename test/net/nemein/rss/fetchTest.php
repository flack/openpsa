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
class net_nemein_rss_fetchTest extends openpsa_testcase
{
    public function test_import_article()
    {
        $topic = $this->create_object('midcom_db_topic', array('component' => 'net.nehmer.blog'));
        $feed = $this->create_object('net_nemein_rss_feed_dba', array('node' => $topic->id));
        $fetcher = new net_nemein_rss_fetch($feed);

        $now = time();
        $item = array
        (
            'title' => 'import test',
            'guid' => '12345',
            'link' => 'http://openpsa2.org',
            'description' => 'test description',
            'category' => 'test category, test2',
            'date_timestamp' => $now
        );
        midcom::get('auth')->request_sudo('net.nemein.rss');
        $guid = $fetcher->import_item($item);
        midcom::get('auth')->drop_sudo();
        $this->assertTrue(mgd_is_guid($guid));
        $article = new midcom_db_article($guid);
        $this->register_object($article);
        $this->assertEquals('import-test', $article->name);
        $this->assertEquals('import test', $article->title);
        $this->assertEquals('test description', $article->content);
        $this->assertEquals('http://openpsa2.org', $article->url);
        $this->assertEquals('|feed:' . md5($feed->url) . '|test category|test2|', $article->extra1);

        $feed->refresh();
        $this->assertEquals($now, $feed->latestupdate);

        //Now for the update
        $now = time() + 10;
        $item = array
        (
            'title' => 'import test 2',
            'guid' => '12345',
            'link' => 'http://openpsa2.org',
            'description' => 'test description',
            'category' => 'test category',
            'date_timestamp' => $now
        );

        midcom::get('auth')->request_sudo('net.nemein.rss');
        $guid2 = $fetcher->import_item($item);
        midcom::get('auth')->drop_sudo();
        $this->assertTrue(mgd_is_guid($guid2));
        $article = new midcom_db_article($guid2);
        $this->register_object($article);

        $this->assertEquals($guid, $guid2);
        $this->assertEquals('import-test', $article->name);
        $this->assertEquals('import test 2', $article->title);
        $this->assertEquals('|feed:' . md5($feed->url) . '|test category|', $article->extra1);

        $feed->refresh();
        $this->assertEquals($now, $feed->latestupdate);
    }
}