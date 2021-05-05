<?php
/**
 * @package net.nemein.rss
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\DomCrawler\Crawler;
use midcom\dba\softdelete;

/**
 * RSS and Atom feed fetching class. Caches the fetched items as articles
 * in net.nehmer.blog
 *
 * @package net.nemein.rss
 */
class net_nemein_rss_fetch extends midcom_baseclasses_components_purecode
{
    /**
     * The last error reported by SimplePie, if any
     */
    public $lasterror;

    /**
     * @var net_nemein_rss_feed_dba
     */
    private $_feed;

    /**
     * Property of midcom_db_article we're using for storing the feed item GUIDs
     */
    private $_guid_property = 'extra2';

    /**
     * Current node we're importing to
     *
     * @var midcom_db_topic
     */
    private $_node;

    /**
     * Initializes the class with a given feed
     */
    public function __construct(net_nemein_rss_feed_dba $feed)
    {
        $this->_feed = $feed;
        $this->_node = new midcom_db_topic($feed->node);

        parent::__construct();
    }

    public static function get_parser() : SimplePie
    {
        $parser = new SimplePie;
        $parser->get_registry()->register('Item', net_nemein_rss_parser_item::class);
        $parser->set_output_encoding(midcom::get()->i18n->get_current_charset());
        $parser->set_cache_location(midcom::get()->config->get('midcom_tempdir'));
        return $parser;
    }

    /**
     * Actually fetch a feed
     */
    public static function raw_fetch(string $url) : SimplePie
    {
        $parser = self::get_parser();
        $parser->set_feed_url($url);
        $parser->init();
        return $parser;
    }

    /**
     * Fetch given RSS or Atom feed
     *
     * @return net_nemein_rss_parser_item[] Array of normalized feed items
     */
    function fetch() : array
    {
        $parser = self::raw_fetch($this->_feed->url);
        if ($parser->error()) {
            $this->lasterror = $parser->error();
            return [];
        }
        if (!empty($parser->data['headers']['etag'])) {
            // Etag checking
            $etag = trim($parser->data['headers']['etag']);

            $feed_etag = $this->_feed->get_parameter('net.nemein.rss', 'etag');
            if (   !empty($feed_etag)
                && $feed_etag == $etag) {
                // Feed hasn't changed, skip updating
                debug_add("Feed {$this->_feed->url} has not changed since " . date('c', $this->_feed->latestfetch), MIDCOM_LOG_WARN);
                return [];
            }

            $this->_feed->set_parameter('net.nemein.rss', 'etag', $etag);
        }

        $this->_feed->latestfetch = time();
        $this->_feed->_use_rcs = false;
        $this->_feed->update();

        return $parser->get_items();
    }

    /**
     * Fetches and imports items in the feed
     */
    public function import() : array
    {
        if (!$this->_node->component) {
            return [];
        }

        $items = $this->fetch();

        if (empty($items)) {
            // This feed didn't return any items, skip
            return [];
        }

        // Reverse items so that creation times remain in correct order even for feeds without timestamps
        $items = array_reverse($items);

        foreach ($items as $item) {
            if ($guid = $this->import_item($item)) {
                $item->set_local_guid($guid);
                debug_add("Imported item " . $item->get_id() . ' as ' . $guid, MIDCOM_LOG_INFO);
            } else {
                debug_add("Failed to import item " . $item->get_id() . ': ' . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            }
        }

        $this->clean($items);

        return array_reverse($items);
    }

    /**
     * Imports a feed item into the database
     */
    public function import_item(net_nemein_rss_parser_item $item) : ?string
    {
        if ($this->_node->component !== 'net.nehmer.blog') {
            throw new midcom_error("RSS fetching for component {$this->_node->component} is unsupported");
        }
        return $this->import_article($item);
    }

    /**
     * Imports an item as a news article
     */
    private function import_article(net_nemein_rss_parser_item $item) : ?string
    {
        $guid = $item->get_id();
        $title = $item->get_title();

        if (   (   empty($title)
                || trim($title) == '...')
            && empty($guid)) {
            // Something wrong with this entry, skip it
            return null;
        }

        $article = $this->find_article($item, $guid);
        if (!$article) {
            return null;
        }

        $article->allow_name_catenate = true;
        $article->set_rcs_message(sprintf(midcom::get()->i18n->get_string('%s was imported from %s', 'net.nemein.rss'), $title, $this->_feed->title));

        $values = [
            'title' => $title,
            $this->_guid_property => $guid, // FIXME: This breaks with URLs longer than 255 chars
            'content' => $item->get_content(),
            'url' => $item->get_link(),
            'extra1' => '|feed:' . md5($this->_feed->url) . '|',
        ];
        $meta_values = [];

        // Safety, make sure we have sane name (the allow_catenate was set earlier, so this will not clash
        if (empty($article->name)) {
            $values['name'] = midcom_helper_misc::urlize($title);
        }

        $categories = $item->get_categories();
        if (is_array($categories)) {
            // Handle categories provided in the feed
            foreach ($categories as $category) {
                // Clean up the categories and save
                $category = str_replace('|', '_', trim($category->get_term()));
                $values['extra1'] .= "{$category}|";
            }
        }

        $article_author = $this->find_author($item);
        if (!empty($article_author->guid)) {
            $meta_values['authors'] = "|{$article_author->guid}|";
        }

        // Try to figure out item publication date
        $article_date = $item->get_date('U');

        $article_data_tweaked = false;
        if (!$article_date) {
            $article_date = time();
            $article_data_tweaked = true;
        }

        if ($article_date > $this->_feed->latestupdate) {
            // Cache "latest updated" time to feed
            $this->_feed->latestupdate = $article_date;
            $this->_feed->_use_rcs = false;
            $this->_feed->update();
        }

        if ($article->id) {
            if (!$article_data_tweaked) {
                $meta_values['published'] = $article_date;
            }

            if (   $this->apply_values($article, $values, $meta_values)
                && !$article->update()) {
                return null;
            }
        } else {
            $this->apply_values($article, $values, $meta_values);
            if (!$article->create()) {
                return null;
            }
        }

        if ($this->_feed->autoapprove) {
            $article->metadata->approve();
        }

        $this->_parse_tags($article);
        $this->_parse_parameters($article, $item);

        // store <link rel="replies"> url in parameter
        if ($item->get_link(0, 'replies')) {
            $article->set_parameter('net.nemein.rss', 'replies_url', $item->get_link(0, 'replies'));
        }

        return $article->guid;
    }

    private function find_author(net_nemein_rss_parser_item $item) : midcom_db_person
    {
        // Try to figure out item author
        if (   $this->_feed->forceauthor
            && $this->_feed->defaultauthor) {
            // Feed has a "default author" set, use it
            return new midcom_db_person($this->_feed->defaultauthor);
        }
        $author = $this->match_item_author($item);
        $fallback_person_id = 1;
        if (   !$author
            || $author->id == $fallback_person_id) {
            if ($this->_feed->defaultauthor) {
                // Feed has a "default author" set, use it
                $author = new midcom_db_person($this->_feed->defaultauthor);
            } else {
                // Fall back to "Midgard Admin" just in case
                $author = new midcom_db_person($fallback_person_id);
            }
        }
        return $author;
    }

    private function find_article(net_nemein_rss_parser_item $item, string $guid) : ?midcom_db_article
    {
        $qb = midcom_db_article::new_query_builder();
        $qb->add_constraint('topic', '=', $this->_feed->node);
        $qb->add_constraint($this->_guid_property, '=', substr($guid, 0, 255));
        $articles = $qb->execute();
        if (!empty($articles)) {
            // This item has been imported already earlier. Update
            return $articles[0];
        }

        // Check against duplicate hits that may come from different feeds
        if ($link = $item->get_link()) {
            $qb = midcom_db_article::new_query_builder();
            $qb->add_constraint('topic', '=', $this->_feed->node);
            $qb->add_constraint('url', '=', $link);
            if ($qb->count() > 0) {
                // Dupe, skip
                return null;
            }
        }

        // This is a new item
        $article = new midcom_db_article();
        $article->topic = $this->_feed->node;
        return $article;
    }

    private function apply_values(midcom_db_article $article, array $values, array $meta_values) : bool
    {
        $updated = false;

        foreach ($values as $fieldname => $value) {
            if ($article->$fieldname !== $value) {
                $article->$fieldname = $value;
                $updated = true;
            }
        }

        foreach ($meta_values as $fieldname => $value) {
            if ($article->metadata->$fieldname !== $value) {
                $article->metadata->$fieldname = $value;
                $updated = true;
            }
        }

        return $updated;
    }

    /**
     * Cleans up old, removed items from feeds
     *
     * @param net_nemein_rss_parser_item[] $items
     */
    private function clean(array $items)
    {
        if ($this->_feed->keepremoved) {
            // This feed is set up so that we retain items removed from array
            return;
        }

        // Create array of item GUIDs
        $item_guids = [];
        foreach ($items as $item) {
            $item_guids[] = $item->get_id();
        }

        // Find articles resulting from this feed
        $qb = midcom_db_article::new_query_builder();
        $feed_category = md5($this->_feed->url);
        $qb->add_constraint('extra1', 'LIKE', "%|feed:{$feed_category}|%");
        $qb->add_constraint($this->_guid_property, 'NOT IN', $item_guids);
        $local_items = $qb->execute_unchecked();
        $purge_guids = [];
        foreach ($local_items as $item) {
            $purge_guids[] = $item->guid;
            $item->delete();
        }

        softdelete::purge($purge_guids, 'midgard_article');
    }

    /**
     * Parses author formats used by different feed standards and
     * and returns the information
     */
    public static function parse_item_author(net_nemein_rss_parser_item $item) : array
    {
        $author_info = [];

        // First try dig up any information about the author possible
        if ($author = $item->get_author()) {
            $name = $author->get_name();
            $email = $author->get_email();
            if (!empty($name)) {
                $name = html_entity_decode($name, ENT_QUOTES, midcom::get()->i18n->get_current_charset());
                // Atom feed, the value can be either full name or username
                $author_info['user_or_full'] = $name;
            } else {
                $name = html_entity_decode($email, ENT_QUOTES, midcom::get()->i18n->get_current_charset());
            }

            if (!preg_match('/(<|\()/', $name)) {
                $author_info['user_or_full'] = $name;
            } else {
                if (strstr($name, '<')) {
                    // The classic "Full Name <email>" format
                    $regex = '/(?<fullname>.+) <?(?<email>[a-zA-Z0-9_.-]+?@[a-zA-Z0-9_.-]+)>?[ ,]?/';
                } else {
                    // The classic "email (Full Name)" format
                    $regex = '/^(?<email>[a-zA-Z0-9_.-]+?@[a-zA-Z0-9_.-]+) \((?<fullname>.+)\)$/';
                }
                if (preg_match($regex, $name, $matches)) {
                    $author_info['email'] = $matches['email'];
                    $author_info['user_or_full'] = $matches['fullname'];
                }
            }
        }

        if (isset($author_info['user_or_full'])) {
            $author_info['user_or_full'] = trim($author_info['user_or_full']);
            if (strstr($author_info['user_or_full'], ' ')) {
                // This value has a space in it, assuming full name
                $author_info['full_name'] = $author_info['user_or_full'];
            } else {
                $author_info['username'] = $author_info['user_or_full'];
            }
            unset($author_info['user_or_full']);
        }

        return $author_info;
    }

    /**
     * Parses author formats used by different feed standards and
     * tries to match to persons in database.
     */
    public function match_item_author(net_nemein_rss_parser_item $item) : ?midcom_db_person
    {
        // Parse the item for author information
        $author_info = self::parse_item_author($item);

        if (!empty($author_info['email'])) {
            // Email is a pretty good identifier, start with it
            $person_qb = midcom_db_person::new_query_builder();
            $person_qb->add_constraint('email', '=', $author_info['email']);
            $persons = $person_qb->execute();
            if (!empty($persons)) {
                return $persons[0];
            }
        }

        if (   !empty($author_info['username'])
            && $person = midcom::get()->auth->get_user_by_name($author_info['username'])) {
            return $person->get_storage();
        }

        if (!empty($author_info['full_name'])) {
            $name_parts = explode(' ', $author_info['full_name']);
            if (count($name_parts) > 1) {
                // We assume the western format Firstname Lastname
                $firstname = $name_parts[0];
                $lastname = $name_parts[1];

                $person_qb = midcom_db_person::new_query_builder();
                $person_qb->add_constraint('firstname', '=', $firstname);
                $person_qb->add_constraint('lastname', '=', $lastname);
                $persons = $person_qb->execute();
                if (!empty($persons)) {
                    return $persons[0];
                }
            }
        }

        return null;
    }

    /**
     * Parses additional metadata in RSS item and sets parameters accordingly
     */
    private function _parse_parameters(midcom_db_article $article, net_nemein_rss_parser_item $item)
    {
        foreach ($item->get_enclosures() as $enclosure) {
            $article->set_parameter('net.nemein.rss:enclosure', 'url', $enclosure->get_link());
            $article->set_parameter('net.nemein.rss:enclosure', 'duration', $enclosure->get_duration());
            $article->set_parameter('net.nemein.rss:enclosure', 'mimetype', $enclosure->get_type());
        }
    }

    /**
     * Parses rel-tag links in article content and tags the object based on them
     */
    private function _parse_tags(midcom_db_article $article)
    {
        $crawler = new Crawler($article->content);
        $nodes = $crawler->filter('a[rel="tag"]');

        $html_tags = $nodes->each(function(Crawler $node) {
            return [
                'href' => $node->attr('href') ?? false,
                'value' => $node->text() ?? false,
            ];
        });

        $tags = [];

        foreach ($html_tags as $html_tag) {
            if (!$html_tag['value']) {
                // No actual tag specified, skip
                continue;
            }

            $tag = strtolower(strip_tags($html_tag['value']));
            $tags[$tag] = $html_tag['href'];
        }
        if (!empty($tags)) {
            net_nemein_tag_handler::tag_object($article, $tags, $this->_node->component);
        }
    }
}
