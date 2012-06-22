<?php
/**
 * @package net.nehmer.comments
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cron handler for fetching comments from Qaiku
 *
 * @package net.nehmer.comments
 */
class net_nehmer_comments_cron_qaiku extends midcom_baseclasses_components_cron_handler
{
    function _on_execute()
    {
        debug_add('_on_execute called');

        if (!$this->_config->get('qaiku_enable'))
        {
            debug_add('Qaiku import disabled, aborting', MIDCOM_LOG_INFO);
            return;
        }

        if (!midcom::get('auth')->request_sudo('net.nehmer.comments'))
        {
            debug_add('Could not get sudo, aborting operation', MIDCOM_LOG_ERROR);
            return;
        }

        // Get 50 latest articles so we can look for those
        $articles_by_url = array();
        $qb = midcom_db_article::new_query_builder();
        $qb->add_constraint('topic.guid', '=', $this->_config->get('qaiku_topic'));
        $qb->add_order('metadata.published', 'DESC');
        $qb->set_limit(20);
        $articles = $qb->execute();
        foreach ($articles as $article)
        {
            $articles_by_url[midcom::get('permalinks')->resolve_permalink($article->guid)] = $article;
        }
        unset($articles);

        foreach ($articles_by_url as $article_url => $article)
        {
            // Get the Qaiku JSON feed for article
            $url = "http://www.qaiku.com/api/statuses/replies/byurl.json?external_url=" . urlencode($article_url) . "&apikey=" . $this->_config->get('qaiku_apikey');
            $json_file = @file_get_contents($url);
            if (!$json_file)
            {
                continue;
            }

            $comments = json_decode($json_file);
            if (empty($comments))
            {
                continue;
            }

            foreach ($comments as $entry)
            {
                $article->set_parameter('net.nehmer.comments', 'qaiku_url', $entry->in_reply_to_status_url);

                $entry_published = strtotime($entry->created_at);

                // Check this comment isn't there yet
                $qb = net_nehmer_comments_comment::new_query_builder();
                $qb->add_constraint('author', '=', (string) $entry->user->name);
                $qb->add_constraint('metadata.published', '=', gmstrftime('%Y-%m-%d %T', $entry_published));
                $qb->add_constraint('objectguid', '=', $article->guid);
                $comments = $qb->execute();
                if (count($comments) > 0)
                {
                    // Update comment as needed
                    $comment = $comments[0];
                    if ($comment->content != $entry->html)
                    {
                        // Entry has been updated
                        $comment->content = (string) $entry->html;
                        $comment->update();
                    }
                    unset($comments);
                    continue;
                }

                $comment = new net_nehmer_comments_comment();
                $comment->objectguid = $article->guid;
                $comment->author = $entry->user->name;
                $comment->content = $entry->html;
                $comment->metadata->published = $entry_published;
                $comment->status = $this->_config->get('qaiku_initial_status');
                $comment->create();
            }
        }
        midcom::get('auth')->drop_sudo();

        debug_add('Done');
    }
}
