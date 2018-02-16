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
        $string = file_get_contents($source);
        $feed = net_nemein_rss_fetch::get_parser();
        $feed->set_raw_data($string);
        $feed->init();
        return $feed->get_items();
    }

    public function test_import_article()
    {
        $topic = $this->create_object(midcom_db_topic::class, ['component' => 'net.nehmer.blog']);
        $attributes = [
            'node' => $topic->id,
            'url' => 'http://openpsa2.org/'
        ];
        $feed = $this->create_object(net_nemein_rss_feed_dba::class, $attributes);
        $fetcher = new net_nemein_rss_fetch($feed);

        $items = $this->_get_items(__DIR__ . '/__files/article.xml');

        midcom::get()->auth->request_sudo('net.nemein.rss');
        $guid = $fetcher->import_item($items[0]);
        midcom::get()->auth->drop_sudo();
        $this->assertTrue(mgd_is_guid($guid));
        $article = new midcom_db_article($guid);
        $this->register_object($article);

        $this->assertEquals('import-test', $article->name);
        $this->assertEquals('Import Test', $article->title);
        $this->assertEquals('Test Description', $article->content);
        $this->assertEquals('http://openpsa2.org/news/no-such-entry/', $article->url);
        $this->assertEquals('|feed:' . md5($feed->url) . '|test category|test2|', $article->extra1);

        $this->assertEquals('video/wmv', $article->get_parameter('net.nemein.rss:enclosure', 'mimetype'));
        $this->assertEquals('http://openpsa2.org/no-such-video', $article->get_parameter('net.nemein.rss:enclosure', 'url'));

        $feed->refresh();
        $this->assertEquals(1362342210, $feed->latestupdate);

        //Now for the update
        $update_items = $this->_get_items(__DIR__ . '/__files/article_update.xml');

        midcom::get()->auth->request_sudo('net.nemein.rss');
        $guid2 = $fetcher->import_item($update_items[0]);
        midcom::get()->auth->drop_sudo();
        $this->assertTrue(mgd_is_guid($guid2));
        $article = new midcom_db_article($guid2);
        $this->register_object($article);

        $this->assertEquals($guid, $guid2);
        $this->assertEquals('import-test', $article->name);
        $this->assertEquals('Import Test 2', $article->title);
        $this->assertEquals('|feed:' . md5($feed->url) . '|test category|', $article->extra1);

        $feed->refresh();
        $this->assertEquals(1362349410, $feed->latestupdate);
    }

    public function test_match_item_author()
    {
        $topic = self::get_component_node('net.nehmer.blog');
        $feed = new net_nemein_rss_feed_dba;
        $feed->node = $topic->id;
        $fetcher = new net_nemein_rss_fetch($feed);
        $pie = net_nemein_rss_fetch::get_parser();
        $pie->set_raw_data(file_get_contents(__DIR__ . '/__files/empty.xml'));
        $pie->init();
        $item = $pie->get_item();

        $person = self::create_user();
        $user = midcom::get()->auth->get_user($person->id);
        $item->data['child']['']['author'][0]['data'] = $user->username;

        $author = $fetcher->match_item_author($item);
        $this->assertInstanceOf(midcom_db_person::class, $author);
        $this->assertEquals($person->guid, $author->guid);

        $email = uniqid() . '@openpsa2.org';
        $person = $this->create_object(midcom_db_person::class, ['email' => $email]);

        $item->data['child']['']['author'][0]['data'] = 'test <' . $email . '>';
        $author = $fetcher->match_item_author($item);
        $this->assertInstanceOf(midcom_db_person::class, $author);
        $this->assertEquals($person->guid, $author->guid);

        $attributes = [
            'firstname' => uniqid('firstname'),
            'lastname' => uniqid('lastname')
        ];

        $person = $this->create_object(midcom_db_person::class, $attributes);

        $item->data['child']['']['author'][0]['data'] = $attributes['firstname'] . ' ' . $attributes['lastname'];

        $author = $fetcher->match_item_author($item);
        $this->assertInstanceOf(midcom_db_person::class, $author);
        $this->assertEquals($person->guid, $author->guid);
    }

    /**
     * @dataProvider provider_normalize_item
     */
    public function test_normalize_item($item, $expected)
    {
        foreach ($expected as $field => $value) {
            $method = 'get_' . $field;
            $this->assertEquals($value, $item->$method(), 'difference in field ' . $field);
        }
    }

    public function provider_normalize_item()
    {
        $items = $this->_get_items(__DIR__ . '/__files/normalize.xml', true);
        return [
            [
                $items[0],
                [
                    'id' => 'http://openpsa2.org/midcom-permalink-nosuchguid',
                    'title' => 'Untitled',
                    'link' => 'http://openpsa2.org/midcom-permalink-nosuchguid',
                    'description' => ''
                ]
            ],
            [
                $items[1],
                [
                    'id' => 'http://openpsa2.org/midcom-permalink-nosuchlink',
                    'title' => 'Test Description...',
                    'link' => 'http://openpsa2.org/midcom-permalink-nosuchlink',
                    'description' => '<a href="http://localhost/">Test Description</a>',
                ]
            ],
            [
                $items[2],
                [
                    'id' => '',
                    'title' => 'Test Description...',
                    'link' => '',
                    'description' => '<a href="http://localhost">Test Description</a>',
                ]
            ],
        ];
    }
}
