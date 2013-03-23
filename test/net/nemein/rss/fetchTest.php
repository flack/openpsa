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
    private function _get_items($source, $raw = false)
    {
        require_once MIDCOM_ROOT . '/external/magpierss/rss_fetch.inc';
        require_once MIDCOM_ROOT . '/external/magpierss/rss_parse.inc';
        require_once MIDCOM_ROOT . '/external/magpierss/rss_cache.inc';
        require_once MIDCOM_ROOT . '/external/magpierss/rss_utils.inc';

        $string = file_get_contents($source);
        $rss = new MagpieRSS($string);
        if ($raw)
        {
            return $rss->items;
        }
        $return = array();
        foreach ($rss->items as $item)
        {
            $return[] = net_nemein_rss_fetch::normalize_item($rss->items[0]);
        }
        return $return;
    }

    public function test_import_article()
    {
        $topic = $this->create_object('midcom_db_topic', array('component' => 'net.nehmer.blog'));
        $attributes = array
        (
            'node' => $topic->id,
            'url' => 'http://openpsa2.org/'
        );
        $feed = $this->create_object('net_nemein_rss_feed_dba', $attributes);
        $fetcher = new net_nemein_rss_fetch($feed);

        $items = @$this->_get_items(__DIR__ . '/__files/article.xml');

        midcom::get('auth')->request_sudo('net.nemein.rss');
        $guid = $fetcher->import_item($items[0]);
        midcom::get('auth')->drop_sudo();
        $this->assertTrue(mgd_is_guid($guid));
        $article = new midcom_db_article($guid);
        $this->register_object($article);
        $this->assertEquals('import-test', $article->name);
        $this->assertEquals('Import Test', $article->title);
        $this->assertEquals('Test Description', $article->content);
        $this->assertEquals('http://openpsa2.org/news/no-such-entry/', $article->url);
        $this->assertEquals('|feed:' . md5($feed->url) . '|test category|test2|', $article->extra1);

        $feed->refresh();
        $this->assertEquals(1362342210, $feed->latestupdate);

        //Now for the update
        $now = time() + 10;
        $item = array
        (
            'title' => 'import test 2',
            'guid' => 'http://openpsa2.org/midcom-permalink-nosuchguid',
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

    public function test_match_item_author()
    {
        $topic = self::get_component_node('net.nehmer.blog');
        $feed = new net_nemein_rss_feed_dba;
        $feed->node = $topic->id;
        $fetcher = new net_nemein_rss_fetch($feed);

        $person = self::create_user();
        $user = midcom::get('auth')->get_user($person->id);

        $input = array
        (
            'author' => $user->username
        );

        $author = $fetcher->match_item_author($input);
        $this->assertInstanceOf('midcom_db_person', $author);
        $this->assertEquals($person->guid, $author->guid);

        $email = microtime(true) . '@openpsa2.org';
        $person = $this->create_object('midcom_db_person', array('email' => $email));

        $input = array
        (
            'author' => 'test <' . $email . '>'
        );
        $author = $fetcher->match_item_author($input);
        $this->assertInstanceOf('midcom_db_person', $author);
        $this->assertEquals($person->guid, $author->guid);

        $attributes = array
        (
            'firstname' => microtime(true),
            'lastname' => microtime(true)
        );

        $person = $this->create_object('midcom_db_person', $attributes);

        $input = array
        (
            'author' => $attributes['firstname'] . ' ' . $attributes['lastname']
        );
        $author = $fetcher->match_item_author($input);
        $this->assertInstanceOf('midcom_db_person', $author);
        $this->assertEquals($person->guid, $author->guid);
    }

    /**
     * @dataProvider provider_normalize_item
     */
    public function test_normalize_item($item, $expected)
    {
        $normalized = net_nemein_rss_fetch::normalize_item($item);
        $this->assertEquals($expected, $normalized);
    }

    public function provider_normalize_item()
    {
        $items = @$this->_get_items(__DIR__ . '/__files/normalize.xml', true);
        return array
        (
            array
            (
                $items[0],
                array
                (
                    'guid' => 'http://openpsa2.org/midcom-permalink-nosuchguid',
                    'title' => 'Untitled',
                    'link' => 'http://openpsa2.org/midcom-permalink-nosuchguid',
                    'description' => ''
                )
            ),
            array
            (
                $items[1],
                array
                (
                    'guid' => 'http://openpsa2.org/midcom-permalink-nosuchlink',
                    'title' => 'Test Description...',
                    'link' => 'http://openpsa2.org/midcom-permalink-nosuchlink',
                    'description' => '<a href="http://localhost">Test Description</a>',
                    'summary' => '<a href="http://localhost">Test Description</a>'
                )
            ),
            array
            (
                $items[2],
                array
                (
                    'guid' => '',
                    'title' => strftime('%x', 1362342210),
                    'link' => '',
                    'description' => '<a href="http://localhost">Test Description</a>',
                    'pubdate' => 'Sun, 03 Mar 2013 20:23:30 +0000',
                    'dc' => array
                    (
                        'description' => '<a href="http://localhost">Test Description</a>',
                    ),
                    'date_timestamp' => 1362342210
                )
            ),
        );
    }
}