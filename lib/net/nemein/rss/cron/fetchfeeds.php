<?php
/**
 * @package net.nemein.rss
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: fetchplazes.php 3757 2006-07-27 14:32:42Z bergie $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cron handler for fetching subscribed RSS and Atom feeds
 * @package net.nemein.rss
 */
class net_nemein_rss_cron_fetchfeeds extends midcom_baseclasses_components_cron_handler
{
    /**
     * Fetches subscribed feeds and imports them
     */
    function _on_execute()
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        debug_add('_on_execute called');
        if (!$_MIDCOM->auth->request_sudo('net.nemein.rss'))
        {
            $msg = "Could not get sudo, aborting operation, see error log for details";
            $this->print_error($msg);
            debug_add($msg, MIDCOM_LOG_ERROR);
            debug_pop();
            return;
        }

        //Disable limits
        // TODO: Could this be done more safely somehow
        @ini_set('memory_limit', -1);
        @ini_set('max_execution_time', 0);

        $qb = net_nemein_rss_feed_dba::new_query_builder();
        // Process lang0 subscriptions first
        $qb->add_order('itemlang', 'ASC');
        $feeds = $qb->execute();
        foreach ($feeds as $feed)
        {
            $node = new midcom_db_topic($feed->node);
            if (   !$node
                || !$node->guid)
            {
                debug_add("Node #{$feed->node} does not exist, skipping feed #{$feed->id}", MIDCOM_LOG_ERROR);
                continue;
            }
            debug_add("Fetching {$feed->url}...", MIDCOM_LOG_INFO);
            $fetcher = new net_nemein_rss_fetch($feed);
            $items = $fetcher->import();
            debug_add("Imported " . count($items) . " items, set feed refresh time to " . strftime('%x %X', $feed->latestfetch), MIDCOM_LOG_INFO);
        }
        $_MIDCOM->auth->drop_sudo();

        debug_add('Done');
        debug_pop();
        return;
    }
}
?>