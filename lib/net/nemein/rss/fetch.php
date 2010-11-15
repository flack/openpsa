<?php
/**
 * @package net.nemein.rss
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: importer.php 3757 2006-07-27 14:32:42Z bergie $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

require_once(MIDCOM_ROOT . '/net/nemein/rss/magpierss/rss_fetch.inc');
require_once(MIDCOM_ROOT . '/net/nemein/rss/magpierss/rss_parse.inc');
require_once(MIDCOM_ROOT . '/net/nemein/rss/magpierss/rss_cache.inc');
require_once(MIDCOM_ROOT . '/net/nemein/rss/magpierss/rss_utils.inc');

/**
 * RSS and Atom feed fetching class. Caches the fetched items as articles
 * in net.nehmer.blog or events in net.nemein.calendar
 *
 * @package net.nemein.rss
 */
class net_nemein_rss_fetch extends midcom_baseclasses_components_purecode
{
    /**
     * The feed object we're fetching
     */
    var $_feed;

    /**
     * Timestamp, when was the latest item in the feed updated
     */
    var $_feed_updated;

    /**
     * Property of midcom_db_article we're using for storing the feed item GUIDs
     */
    var $_guid_property = 'extra2';

    /**
     * Current node we're importing to
     * @var midcom_db_topic
     */
    var $_node = null;

    /**
     * Configuration of node we're importing to
     * @var midcom_helper_configuration
     */
    var $_node_config = null;

    /**
     * Datamanager for handling saves
     * @var midcom_helper_datamanager2
     */
    var $_datamanager = null;

    /**
     * Initializes the class with a given feed
     */
    function __construct($feed)
    {
        $this->_feed = $feed;

        $this->_node = new midcom_db_topic($this->_feed->node);

        $this->_component = 'net.nemein.rss';

        $_MIDCOM->load_library('org.openpsa.httplib');

        if ($this->_node->component)
        {
            if (!isset($GLOBALS['midcom_component_data'][$this->_node->component]))
            {
                $_MIDCOM->componentloader->load_graceful($this->_node->component);
            }
            $this->_node_config = $GLOBALS['midcom_component_data'][$this->_node->component]['config'];
        }
        parent::__construct();
    }

    /**
     * Static method for actually fetching a feed
     */
    function raw_fetch($url)
    {
        $items = array();

        if (!$_MIDCOM->componentloader->is_loaded('org.openpsa.httplib'))
        {
            $_MIDCOM->load_library('org.openpsa.httplib');
        }

        try
        {
            // TODO: Ensure Magpie uses conditional GETs here
            error_reporting(E_WARNING);
            $rss = @fetch_rss($url);
            error_reporting(E_ALL);
        }
        catch (Exception $e)
        {
            // Magpie failed fetching or parsing the feed somehow
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Failed to fetch or parse feed {$url}: " . $e->getMessage(), MIDCOM_LOG_INFO);
            debug_pop();
            return $items;
        }

        if (!$rss)
        {
            // Magpie failed fetching or parsing the feed somehow
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Failed to fetch or parse feed {$url}: " . $GLOBALS['MAGPIE_ERROR'], MIDCOM_LOG_INFO);
            debug_pop();
            return $items;
        }

        foreach ($rss->items as $item)
        {
            // Normalize the item
            $item = net_nemein_rss_fetch::normalize_item($item);

            if ($item)
            {
                $items[] = $item;
            }
        }
        $rss->items = $items;

        return $rss;
    }

    /**
     * Fetch given RSS or Atom feed
     *
     * @param Array Array of normalized feed items
     */
    function fetch()
    {
        $rss = net_nemein_rss_fetch::raw_fetch($this->_feed->url);

        if (!$rss)
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("MagpieRSS did not return any items", MIDCOM_LOG_WARN);
            debug_pop();
            return array();
        }

        if (   isset($rss->etag)
            && !empty($rss->etag))
        {
            // Etag checking
            $etag = trim($rss->etag);

            $feed_etag = $this->_feed->get_parameter('net.nemein.rss', 'etag');
            if (   !empty($feed_etag)
                && $feed_etag == $etag)
            {
                // Feed hasn't changed, skip updating
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("Feed {$this->_feed->url} has not changed since " . date('c', $this->_feed->latestfetch), MIDCOM_LOG_WARN);
                debug_pop();
                return array();
            }

            $this->_feed->set_parameter('net.nemein.rss', 'etag', $etag);
        }

        $this->_feed->latestfetch = time();
        $this->_feed->_use_activitystream = false;
        $this->_feed->_use_rcs = false;
        $this->_feed->update();

        return $rss->items;
    }


    /**
     * Fetches and imports items in the feed
     */
    function import()
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        if (!$this->_node->component)
        {
            return array();
        }

        $items = $this->fetch();

        if (count($items) == 0)
        {
            // This feed didn't return any items, skip
            return array();
        }

        // Reverse items so that creation times remain in correct order even for feeds without timestamps
        $items = array_reverse($items);

        foreach ($items as $item_id => $item)
        {
            $items[$item_id]['local_guid'] = $this->import_item($item);

            if (!$items[$item_id]['local_guid'])
            {
                debug_add("Failed to import item {$item['guid']}: " . midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
            }
            else
            {
                debug_add("Imported item {$item['guid']} as {$items[$item_id]['local_guid']}", MIDCOM_LOG_INFO);
            }
        }

        $this->clean($items);

        debug_pop();
        return $items;
    }

    /**
     * Imports a feed item into the database
     *
     * @param Array $item Feed item as provided by MagpieRSS
     */
    function import_item($item)
    {
        $this->normalize_item_link($item);
        switch ($this->_node->component)
        {
            case 'net.nehmer.blog':
                return $this->import_article($item);
                break;

            case 'net.nemein.calendar':
                return $this->import_event($item);
                break;

            default:
                /**
                 * This will totally break cron if someone made something stupid (like changed folder component)
                 * on folder that had subscriptions
                 *
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "RSS fetching for component {$this->_node->component} is unsupported");
                // This will exit.
                 */
                debug_push_class(__CLASS__, __FUNCTION__);
                debug_add("RSS fetching for component {$this->_node->component} is unsupported", MIDCOM_LOG_ERROR);
                debug_pop();
                return false;
        }
    }

    /**
     * Imports an item as a news article
     */
    private function import_article($item)
    {
        if (   (   empty($item['title'])
                || trim($item['title']) == '...')
            && empty($item['guid']))
        {
            // Something wrong with this entry, skip it
            return false;
        }

        $guid_property = $this->_guid_property;
        $qb = midcom_db_article::new_query_builder();
        $qb->add_constraint('topic', '=', $this->_feed->node);
        // TODO: Move this to a parameter in Midgard 1.8
        $qb->add_constraint($guid_property, '=', substr($item['guid'], 0, 255));
        $articles = $qb->execute();
        if (count($articles) > 0)
        {
            // This item has been imported already earlier. Update
            $article = $articles[0];
        }
        else
        {
            // Check against duplicate hits that may come from different feeds
            if ($item['link'])
            {
                $qb = midcom_db_article::new_query_builder();
                $qb->add_constraint('topic', '=', $this->_feed->node);
                $qb->add_constraint('url', '=', $item['link']);
                $hits = $qb->count();
                if ($hits > 0)
                {
                    // Dupe, skip
                    return false;
                }
            }

            // This is a new item
            $article = new midcom_db_article();
        }
        // Sanity check
        if (!is_a($article, 'midcom_db_article'))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add('$article is not an instance of midgard_article (or subclass), see debug level logs for object dump', MIDCOM_LOG_ERROR);
            debug_print_r('$article: ', $article);
            debug_pop();
            return false;
        }
        $article->allow_name_catenate = true;

        $updated = false;

        // Copy properties
        if ($article->title != $item['title'])
        {
            $article->title = $item['title'];
            $updated = true;
        }

        // FIXME: This breaks with URLs longer than 255 chars
        if ($article->$guid_property != $item['guid'])
        {
            $article->$guid_property = $item['guid'];
            $updated = true;
        }

        if ($article->content != $item['description'])
        {
            $article->content = $item['description'];
            $updated = true;
        }

        $article->topic = $this->_feed->node;

        if ($article->url != $item['link'])
        {
            $article->url = $item['link'];
            $updated = true;
        }

        $feed_category = 'feed:' . md5($this->_feed->url);
        $orig_extra1 = $article->extra1;
        $article->extra1 = "|{$feed_category}|";

        $article->_activitystream_verb = 'http://community-equity.org/schema/1.0/clone';
        $article->_rcs_message = sprintf($_MIDCOM->i18n->get_string('%s was imported from %s', 'net.nemein.rss'), $article->title, $this->_feed->title);

        // Handle categories provided in the feed
        if (isset($item['category']))
        {
            // Check if we have multiple categories
            if (is_array($item['category']))
            {
                // Some systems provide multiple categories as per in spec
                $categories = $item['category'];
            }
            elseif (strstr($item['category'], ','))
            {
                // Some systems expose multiple categories in single category element
                $categories = explode(',', $item['category']);
            }
            else
            {
                $categories = array();
                $categories[] = $item['category'];
            }

            foreach ($categories as $category)
            {
                // Clean up the categories and save
                $category = str_replace('|', '_', trim($category));
                $article->extra1 .= "{$category}|";
            }
        }

        if ($orig_extra1 != $article->extra1)
        {
            $updated = true;
        }

        // Try to figure out item author
        if (   $this->_feed->forceauthor
            && $this->_feed->defaultauthor)
        {
            // Feed has a "default author" set, use it
            $article_author = new midcom_db_person($this->_feed->defaultauthor);
        }
        else
        {
            $article_author = $this->match_item_author($item);
            $fallback_person_id = 1;
            if (   !$article_author
                || $article_author->id == $fallback_person_id)
            {
                if ($this->_feed->defaultauthor)
                {
                    // Feed has a "default author" set, use it
                    $article_author = new midcom_db_person($this->_feed->defaultauthor);
                }
                else
                {
                    // Fall back to "Midgard Admin" just in case
                    $fallback_author = new midcom_db_person($fallback_person_id);
                    $article_author = $fallback_author;
                }
            }
        }

        if (   is_object($article_author)
            && $article_author->guid)
        {
            if ($article->metadata->authors != "|{$article_author->guid}|")
            {
                $article->metadata->set('authors', "|{$article_author->guid}|");
                $updated = true;
            }
        }

        // Try to figure out item publication date
        $article_date = null;
        if (isset($item['date_timestamp']))
        {
            $article_date = $item['date_timestamp'];
        }
        $article_data_tweaked = false;
        if (!$article_date)
        {
            $article_date = time();
            $article_data_tweaked = true;
        }

        if ($article_date > $this->_feed->latestupdate)
        {
            // Cache "latest updated" time to feed
            $this->_feed->latestupdate = $article_date;
            $this->_feed->_use_activitystream = false;
            $this->_feed->_use_rcs = false;
            $this->_feed->update();
        }

        if ($article->id)
        {
            // store <link rel="replies"> url in parameter
            if (isset($item['link_replies']))
            {
                $article->set_parameter('net.nemein.rss', 'replies_url', $item['link_replies']);
            }

            if (   $article->metadata->published != $article_date
                && !$article_data_tweaked)
            {
                $article->metadata->published = $article_date;
                $updated = true;
            }

            // Safety, make sure we have sane name (the allow_catenate was set earlier, so this will not clash
            if (empty($article->name))
            {
                $article->name = midcom_generate_urlname_from_string($article->title);
                $updated = true;
            }

            if (!$updated)
            {
                // No data changed, avoid unnecessary I/O
                return $article->guid;
            }

            $article->allow_name_catenate = true;
            if ($article->update())
            {
                if ($this->_feed->autoapprove)
                {
                    $metadata = midcom_helper_metadata::retrieve($article);
                    $metadata->approve();
                }

                $this->parse_tags($article, $item);
                $this->parse_parameters($article, $item);

                return $article->guid;
            }

            return false;
        }
        else
        {
            // Safety, make sure we have sane name (the allow_catenate was set earlier, so this will not clash
            if (empty($article->name))
            {
                $article->name = midcom_generate_urlname_from_string($article->title);
            }
            // This is a new item
            $node = new midcom_db_topic($this->_feed->node);
            $node_lang_code = $node->get_parameter('net.nehmer.blog', 'language');
            if ($node->get_parameter('net.nehmer.blog', 'symlink_topic') != '')
            {
                $symlink_topic = new midcom_db_topic($node->get_parameter('net.nehmer.blog', 'symlink_topic'));
                if ($symlink_topic)
                {
                    $article->topic = $symlink_topic->id;
                }
            }
            if ($node_lang_code != '')
            {
                $lang_id = $_MIDCOM->i18n->code_to_id($node_lang_code);
                $article->lang = $lang_id;
            }
            $article->allow_name_catenate = true;
            if ($article->create())
            {
                // store <link rel="replies"> url in parameter
                if (isset($item['link_replies']))
                {
                    $article->set_parameter('net.nemein.rss', 'replies_url', $item['link_replies']);
                }

                // This should be unnecessary but leave it in place just  in case
                if (strlen($article->name) == 0)
                {
                    // Generate something to avoid empty "/" links in case of failures
                    $article->name = time();
                }

                $article->metadata->published = $article_date;
                $article->allow_name_catenate = true;
                $article->update();

                if ($this->_feed->autoapprove)
                {
                    $metadata = midcom_helper_metadata::retrieve($article);
                    $metadata->approve();
                }

                $this->parse_tags($article, $item);
                $this->parse_parameters($article, $item);

                return $article->guid;
            }
            return false;
        }
    }

    /**
     * Imports an item as an event
     */
    private function import_event($item)
    {
        // Check that we're trying to import item suitable to be an event
        if (   !isset($item['xcal'])
            && !isset($item['gd']['when@']))
        {
            // Not an event
            return false;
        }

        // Get start and end times
        $start = null;
        $end = null;
        if (isset($item['xcal']['dtstart']))
        {
            // xCal RSS feed, for example Upcoming or Last.fm
            $start = strtotime($item['xcal']['dtstart']);
        }
        elseif (isset($item['gd']['when@starttime']))
        {
            // gData Atom feed, for example Dopplr
            $start = strtotime($item['gd']['when@starttime']);
        }

        if (isset($item['xcal']['dtend']))
        {
            $end = strtotime($item['xcal']['dtend']);
        }
        elseif (isset($item['gd']['when@starttime']))
        {
            $end = strtotime($item['gd']['when@endtime']);
        }

        if (   !$start
            || !$end)
        {
            return false;
        }

        if (!$this->_datamanager)
        {
            $schemadb = midcom_helper_datamanager2_schema::load_database($this->_node_config->get('schemadb'));
            $this->_datamanager = new midcom_helper_datamanager2_datamanager($schemadb);
        }

        // TODO: Move to real geocoded stuff
        $location_parts = array();
        if (isset($item['xcal']['x-calconnect-venue_adr_x-calconnect-venue-name']))
        {
            $location_parts[] = $item['xcal']['x-calconnect-venue_adr_x-calconnect-venue-name'];
        }
        if (isset($item['xcal']['x-calconnect-venue_adr_x-calconnect-street']))
        {
            $location_parts[] = $item['xcal']['x-calconnect-venue_adr_x-calconnect-street'];
        }
        if (isset($item['xcal']['x-calconnect-venue_adr_x-calconnect-city']))
        {
            $location_parts[] = $item['xcal']['x-calconnect-venue_adr_x-calconnect-city'];
        }

        if (isset($item['gd']['where@valuestring']))
        {
            $wherevalues = explode(' ', $item['gd']['where@valuestring']);
            foreach ($wherevalues as $val)
            {
                $location_parts[] = $val;
            }
        }

        $qb = net_nemein_calendar_event_dba::new_query_builder();
        $qb->add_constraint('node', '=', $this->_feed->node);
        $qb->add_constraint('extra', '=', md5($item['guid']));
        $events = $qb->execute();
        if (count($events) > 0)
        {
            // This item has been imported already earlier. Update
            $event = $events[0];
            $event->_activitystream_verb = 'http://community-equity.org/schema/1.0/clone';
            $event->_rcs_message = sprintf($_MIDCOM->i18n->get_string('%s was imported from %s', 'net.nemein.rss'), $event->title, $this->_feed->title);
            $event->allow_name_catenate = true;
            if (empty($event->name))
            {
                // To prevent validation errors in case the auto-catenate is not allowed in the urlname datatype
                $event->name = midcom_helper_reflector_tree::generate_unique_name($event);
            }
        }
        else
        {
            $node = new midcom_db_topic($this->_feed->node);
            $node_lang_code = $node->get_parameter('net.nemein.calendar', 'language');
            // This is a new item
            $event = new net_nemein_calendar_event_dba();
            $event->start = $start;
            $event->end = $end;
            $event->extra = md5($item['guid']);
            $event->node = $this->_feed->node;
            if ($node->get_parameter('net.nemein.calendar', 'symlink_topic') != '')
            {
                $symlink_topic = new midcom_db_topic($node->get_parameter('net.nemein.calendar', 'symlink_topic'));
                if ($symlink_topic)
                {
                    $event->node = $symlink_topic->id;
                }
            }
            if ($node_lang_code != '')
            {
                $lang_id = $_MIDCOM->i18n->code_to_id($node_lang_code);
                $event->lang = $lang_id;
            }
            $event->allow_name_catenate = true;
            $event->title = (string)$item['title'];
            $event->name = midcom_helper_reflector_tree::generate_unique_name($event);
            $event->_activitystream_verb = 'http://community-equity.org/schema/1.0/clone';
            $event->_rcs_message = sprintf($_MIDCOM->i18n->get_string('%s was imported from %s', 'net.nemein.rss'), $event->title, $this->_feed->title);
            if (!$event->create())
            {
                return false;
            }
        }

        $this->_datamanager->autoset_storage($event);
        $this->_datamanager->types['start']->value = new Date($start);
        $this->_datamanager->types['end']->value = new Date($end);

        if (is_a($this->_datamanager->types['location'], 'midcom_helper_datamanager2_type_position'))
        {
            // Position type, give all values we got, assume order "Street, City, Country"
            $location_parts = array_reverse($location_parts);
            if (count($location_parts) > 0)
            {
                $country = org_routamc_positioning_country_dba::get_by_name($location_parts[0]);
                if (   $country
                    && $country->code)
                {
                    $this->_datamanager->types['location']->location->county = $country->code;
                }
            }
            if (count($location_parts) > 1)
            {
                $city = org_routamc_positioning_city_dba::get_by_name($location_parts[1]);
                if (   $city
                    && $city->id)
                {
                    $this->_datamanager->types['location']->location->city = $city->id;
                }
            }
            if (count($location_parts) > 2)
            {
                $this->_datamanager->types['location']->location->street = $location_parts[2];
            }

            if (isset($item['gml']))
            {
                $gml_parts = explode(' ', $item['gml']['where_point_pos']);
                if (count($gml_parts) == 2)
                {
                    $this->_datamanager->types['location']->location->latitude = (float) $gml_parts[0];
                    $this->_datamanager->types['location']->location->longitude = (float) $gml_parts[1];
                }
            }
        }
        else
        {
            // Just give the location string we got
            $this->_datamanager->types['location']->value = implode(', ', $location_parts);
        }

        foreach ($item as $key => $value)
        {
            if (isset($this->_datamanager->types[$key]))
            {
                $this->_datamanager->types[$key]->value = $value;
            }
        }

        if (!$this->_datamanager->save())
        {
            return false;
        }

        // This should be unnecessary but left in place just to be sure
        if (strlen($this->_datamanager->storage->object->name) == 0)
        {
            // Generate something to avoid empty "/" links in case of failures
            $this->_datamanager->storage->object->name = time();
            $this->_datamanager->storage->object->update();
        }

        $this->parse_tags($event, $item, 'description');
        $this->parse_parameters($event, $item);

        return $event->guid;
    }

    /**
     * Cleans up old, removed items from feeds
     * @param Array $item Feed item as provided by MagpieRSS
     */
    function clean($items)
    {
        if ($this->_feed->keepremoved)
        {
            // This feed is set up so that we retain items removed from array
            return false;
        }

        // Create array of item GUIDs
        $item_guids = array();
        foreach ($items as $item)
        {
            $item_guids[] = $item['guid'];
        }

        // Find articles resulting from this feed
        $qb = midcom_db_article::new_query_builder();
        $feed_category = md5($this->_feed->url);
        $qb->add_constraint('extra1', 'LIKE', "%|feed:{$feed_category}|%");
        $local_items = $qb->execute_unchecked();
        $guid_property = $this->_guid_property;
        $purge_guids = array();
        foreach ($local_items as $item)
        {
            if (!in_array($item->$guid_property, $item_guids))
            {
                // This item has been removed from the feed.

                if (   $_MIDCOM->componentloader->is_installed('net.nemein.favourites')
                    && $_MIDCOM->componentloader->load_graceful('net.nemein.favourites'))
                {
                    // If it has been favorited keep it
                    $qb = net_nemein_favourites_favourite_dba::new_query_builder();
                    $qb->add_constraint('objectGuid', '=', $item->guid);
                    if ($qb->count_unchecked() > 0)
                    {
                        continue;
                        // Skip deleting this one
                    }
                }

                $purge_guids[] = $item->guid;
                $item->delete();
            }
        }

        midcom_baseclasses_core_dbobject::purge($purge_guids, 'midgard_article');

        return true;
    }

    /**
     * Parses author formats used by different feed standards and
     * and returns the information
     *
     * @param Array $item Feed item as provided by MagpieRSS
     * @return Array Information found
     */
    function parse_item_author($item)
    {
        $author_info = array();

        // First try dig up any information about the author possible

        if (isset($item['author']))
        {
            if (strstr($item['author'], '<'))
            {
                // The classic "Full Name <email>" format
                $regex = '/(.+) <?([a-zA-Z0-9_.-]+?@[a-zA-Z0-9_.-]+)>?[ ,]?/';
                if (preg_match_all($regex, $item['author'], $matches_to))
                {
                    foreach ($matches_to[1] as $fullname)
                    {
                        $author_info['user_or_full'] = $fullname;
                    }
                    foreach ($matches_to[2] as $email)
                    {
                        $author_info['email'] = $email;
                    }
                }
            }
            elseif (strstr($item['author'], '('))
            {
                // The classic "email (Full Name)" format
                $regex = '/^([a-zA-Z0-9_.-]+?@[a-zA-Z0-9_.-]+) \((.+)\)$/';
                if (preg_match_all($regex, $item['author'], $matches_to))
                {
                    foreach ($matches_to[1] as $email)
                    {
                        $author_info['email'] = $email;
                    }
                    foreach ($matches_to[2] as $fullname)
                    {
                        $author_info['user_or_full'] = $fullname;
                    }
                }
            }
            else
            {
                $author_info['user_or_full'] = $item['author'];
            }
        }

        if (isset($item['author_name']))
        {
            // Atom feed, the value can be either full name or username
            $author_info['user_or_full'] = $item['author_name'];

        }

        if (isset($item['dc']))
        {
            // We've got Dublin Core metadata
            if (isset($item['dc']['creator']))
            {
                $author_info['user_or_full'] = $item['dc']['creator'];
            }
        }

        if (isset($author_info['user_or_full']))
        {
            if (strstr($author_info['user_or_full'], ' '))
            {
                // This value has a space in it, assuming full name
                $author_info['full_name'] = $author_info['user_or_full'];
            }
            else
            {
                $author_info['username'] = $author_info['user_or_full'];
            }
            unset($author_info['user_or_full']);
        }

        return $author_info;
    }

    /**
     * Parses author formats used by different feed standards and
     * tries to match to persons in database.
     *
     * @param Array $item Feed item as provided by MagpieRSS
     * @return MidgardPerson Person object matched, or NULL
     */
    function match_item_author($item)
    {
        // Parse the item for author information
        $author_info = $this->parse_item_author($item);

        // Start matching the information found to person entries in the database
        $matched_person = null;

        if (isset($author_info['email']))
        {
            // Email is a pretty good identifier, start with it
            $person_qb = midcom_db_person::new_query_builder();
            $person_qb->add_constraint('email', '=', $author_info['email']);
            $persons = $person_qb->execute();
            if (count($persons) > 0)
            {
                $matched_person = $persons[0];
            }
        }

        if (   is_null($matched_person)
            && isset($author_info['username']))
        {
            // Email is a pretty good identifier, start with it
            $person_qb = midcom_db_person::new_query_builder();
            $person_qb->add_constraint('username', '=', strtolower($author_info['username']));
            $persons = $person_qb->execute();
            if (count($persons) > 0)
            {
                $matched_person = $persons[0];
            }
        }

        if (   is_null($matched_person)
            && isset($author_info['full_name']))
        {

            $name_parts = explode(' ', $author_info['full_name']);
            if (count($name_parts) > 1)
            {
                // We assume the western format Firstname Lastname
                $firstname = $name_parts[0];
                $lastname = $name_parts[1];

                $person_qb = midcom_db_person::new_query_builder();
                $person_qb->add_constraint('firstname', '=', $firstname);
                $person_qb->add_constraint('lastname', '=', $lastname);
                $persons = $person_qb->execute();
                if (count($persons) > 0)
                {
                    $matched_person = $persons[0];
                }
            }
        }

        return $matched_person;
    }

    /**
     * Parses additional metadata in RSS item and sets parameters accordingly
     *
     * @param midgard_article $article Imported article
     * @param Array $item Feed item as provided by MagpieRSS
     * @return boolean
     */
    function parse_parameters($article, $item)
    {
        if (isset($item['media']))
        {
            foreach ($item['media'] as $name => $value)
            {
                $article->parameter('net.nemein.rss:media', $name, $value);
            }
        }

        if (isset($item['enclosure@url']))
        {
            $article->parameter('net.nemein.rss:enclosure', 'url', $item['enclosure@url']);
        }

        if (isset($item['enclosure@duration']))
        {
            $article->parameter('net.nemein.rss:enclosure', 'duration', $item['enclosure@duration']);
        }

        if (isset($item['enclosure@type']))
        {
            $article->parameter('net.nemein.rss:enclosure', 'mimetype', $item['enclosure@type']);
        }

        // FeedBurner Awareness API data
        // http://code.google.com/apis/feedburner/awareness_api.html
        if (   isset($item['feedburner'])
            && isset($item['feedburner']['awareness']))
        {
            $article->parameter('com.feedburner', 'awareness', $item['feedburner']['awareness']);
        }

        return true;
    }

    /**
     * Parses rel-tag links in article content and tags the object based on them
     *
     * @param midgard_article $article Imported article
     * @param Array $item Feed item as provided by MagpieRSS
     * @return boolean
     */
    function parse_tags($article, $item, $field = 'content')
    {
        $html_tags = org_openpsa_httplib_helpers::get_anchor_values($article->$field, 'tag');
        $tags = array();

        if (count($html_tags) > 0)
        {
            foreach ($html_tags as $html_tag)
            {
                if (!$html_tag['value'])
                {
                    // No actual tag specified, skip
                    continue;
                }

                $tag = strtolower(strip_tags($html_tag['value']));
                $tags[$tag] = $html_tag['href'];
            }

            $_MIDCOM->load_library('net.nemein.tag');

            return net_nemein_tag_handler::tag_object($article, $tags);
        }

        return true;
    }

    /**
     * In case item link is a relative url try to normalize using the host from feed url
     *
     * @param array $item reference to the item in question
     */
    function normalize_item_link(&$item)
    {
        if (   empty($item['link'])
            || !preg_match('%^/%', $item['link']))
        {
            // Empty or does not start with /
            return;
        }
        static $prefixes = array();
        if (!isset($prefixes[$this->_feed->url]))
        {
            if (!preg_match('%^((.+?)://(.+?))/%', $this->_feed->url, $feed_matches))
            {
                // Could not figure out the host part of feed url
                return;
            }
            $prefixes[$this->_feed->url] = $feed_matches[1];
            unset($feed_matches);
        }
        $prefix =& $prefixes[$this->_feed->url];
        $item['link'] = $prefix . $item['link'];
    }

    /**
     * Normalizes items provided by different feed formats.
     *
     * @param Array $item Feed item as provided by MagpieRSS
     * @param Array Normalized feed item
     */
    function normalize_item($item)
    {

        if (!is_array($item))
        {
            // Broken item, skip
            return false;
        }

        // Fix missing titles
        if (   !isset($item['title'])
            || !$item['title'])
        {
            $item['title'] = $_MIDCOM->i18n->get_string('untitled', 'net.nemein.rss');

            $item_date = $item['date_timestamp'];

            // Check if this item is newer than the others
            if (isset($this))
            {
                if ($item_date > $this->_feed_updated)
                {
                    $this->_feed_updated = $item_date;
                }
            }

            if (isset($item['description']))
            {
                // Use 20 first characters from the description as title
                $item['title'] = substr(strip_tags($item['description']), 0, 20) . '...';
            }
            elseif ($item_date)
            {
                // Use publication date as title
                $item['title'] = strftime('%x', $item_date);
            }
        }

        // Fix missing links
        if (   !isset($item['link'])
            || !$item['link'])
        {
            $item['link'] = '';
            if (isset($item['guid']))
            {
                $item['link'] = $item['guid'];
            }
        }

        if (!array_key_exists('link', $item))
        {
            // No link or GUID defined
            // TODO: Generate a "link" using channel URL
            $item['link'] = '';
        }

        // Fix missing GUIDs
        if (   !isset($item['guid'])
            || !$item['guid'])
        {
            if (isset($item['link']))
            {
                $item['guid'] = $item['link'];
            }
        }

        if (   !isset($item['description'])
            || !$item['description'])
        {
            // Ensure description is always set
            $item['description'] = '';
        }

        if (   isset($item['content'])
            && is_array($item['content'])
            && isset($item['content']['encoded']))
        {
            // Some RSS feeds use "content:encoded" for storing HTML-formatted full item content,
            // so we prefer this instead of simpler description
            $item['description'] = $item['content']['encoded'];
        }

        if ($item['description'] == '')
        {
            // Empty description, fallbacks for some feed formats
            if (   isset($item['dc'])
                && isset($item['dc']['description']))
            {
                $item['description'] = $item['dc']['description'];
            }
            elseif (isset($item['atom_content']))
            {
                // Atom 1.0 feeds store content in the atom_content field
                $item['description'] = $item['atom_content'];
            }
            elseif (strpos($item['link'], 'cws.huginonline.com') !== false)
            {
                // Deal with the funky RSS format provided by Hugin Online
                // Link points to actual news item in hexML format
                $http_client = new org_openpsa_httplib();
                $news_xml = $http_client->get($item['link']);
                $news = simplexml_load_string($news_xml);
                if (isset($news->body->press_releases->press_release->main))
                {
                    $item['description'] = (string) $news->body->press_releases->press_release->main;
                }
            }
        }

        return $item;
    }
}
?>