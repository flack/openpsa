<?php
/**
 * @package net.nemein.rss
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cron handler for fetching subscribed RSS and Atom feeds
 *
 * @package net.nemein.rss
 */
class net_nemein_rss_cron_fetchfeeds extends midcom_baseclasses_components_cron_handler
{
    /**
     * Fetches subscribed feeds and imports them
     */
    public function execute()
    {
        if (!midcom::get()->auth->request_sudo($this->_component)) {
            $this->print_error("Could not get sudo, aborting operation, see error log for details");
            return;
        }

        midcom::get()->disable_limits();

        $qb = net_nemein_rss_feed_dba::new_query_builder();
        foreach ($qb->execute() as $feed) {
            try {
                midcom_db_topic::get_cached($feed->node);
            } catch (midcom_error) {
                debug_add("Node #{$feed->node} does not exist, skipping feed #{$feed->id}", MIDCOM_LOG_ERROR);
                continue;
            }

            debug_add("Fetching {$feed->url}...", MIDCOM_LOG_INFO);
            $fetcher = new net_nemein_rss_fetch($feed);
            $items = $fetcher->import();
            debug_add("Imported " . count($items) . " items, set feed refresh time to " . date('Y-m-d H:i:s', $feed->latestfetch), MIDCOM_LOG_INFO);
        }
        midcom::get()->auth->drop_sudo();
    }
}
