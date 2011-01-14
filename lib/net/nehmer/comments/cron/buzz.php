<?php
/**
 * @package net.nehmer.comments
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cron handler for fetching comments from Google Buzz
 *
 * @package net.nehmer.comments
 */
class net_nehmer_comments_cron_buzz extends midcom_baseclasses_components_cron_handler
{
    function _on_execute()
    {
        debug_add('_on_execute called');

        if (!$this->_config->get('buzz_enable'))
        {
            debug_add('Google Buzz import disabled, aborting', MIDCOM_LOG_INFO);
            return;
        }

        if (!$_MIDCOM->auth->request_sudo('net.nehmer.comments'))
        {
            debug_add('Could not get sudo, aborting operation', MIDCOM_LOG_ERROR);
            return;
        }
        
        // Get 50 latest articles so we can look for those
        $articles_by_title = array();
        $qb = midcom_db_article::new_query_builder();
        $qb->add_constraint('topic.guid', '=', $this->_config->get('buzz_topic'));
        $qb->add_order('metadata.published', 'DESC');
        $qb->set_limit(50);
        $articles = $qb->execute();
        foreach ($articles as $article)
        {
            $articles_by_title[$article->title] = $article;
        }
        unset($articles);

        // Get the Buzz atom feed
        $simplexml = @simplexml_load_file($this->_config->get('buzz_url'));
        if (   !$simplexml
            || !$simplexml->entry)
        {
            debug_add('Google Buzz failed to fetch or parse feed, aborting', MIDCOM_LOG_INFO);
            return;
        }

        foreach ($simplexml->entry as $entry)
        {
            $entry_title = strip_tags($entry->content);
            if (   empty($entry->content)
                || !isset($articles_by_title[$entry_title]))
            {
                // This buzz conversation doesn't match any of the articles
                continue;
            }

            $buzz_url = '';
            $feed_url = '';
            foreach ($entry->link as $link)
            {
                $link_attributes = $link->attributes();
                if (   $link_attributes['rel'] == 'alternate'
                    && $link_attributes['type'] == 'text/html')
                {
                    $buzz_url = $link_attributes['href'];
                    continue;
                }
                if (   $link_attributes['rel'] == 'replies'
                    && $link_attributes['type'] == 'application/atom+xml')
                {
                    $feed_url = $link_attributes['href'];
                }
            }

            if (   !$buzz_url
                || !$feed_url)
            {
                continue;
            }
            
            // Store the URL to the article for later use
            $articles_by_title[$entry_title]->set_parameter('net.nehmer.comments', 'comments_feed', $feed_url);
            $articles_by_title[$entry_title]->set_parameter('net.nehmer.comments', 'comments_url', $buzz_url);
            
            // Fetch replies and store as comments
            $replies = @simplexml_load_file($feed_url);
            if (   !$replies
                || !$replies->entry)
            {
                continue;
            }
            
            foreach ($replies->entry as $entry)
            {
                $entry_published = strtotime((string) $entry->published);
                
                // Check this comment isn't there yet
                $qb = net_nehmer_comments_comment::new_query_builder();
                $qb->add_constraint('author', '=', (string) $entry->author->name);
                $qb->add_constraint('metadata.published', '=', gmstrftime('%Y-%m-%d %T', $entry_published));
                $qb->add_constraint('objectguid', '=', $articles_by_title[$entry_title]->guid);
                $comments = $qb->execute();
                if (count($comments) > 0)
                {
                    // Update comment as needed
                    $comment = $comments[0];
                    if ($comment->content != (string) $entry->content)
                    {
                        // Entry has been updated
                        $comment->content = (string) $entry->content;
                        $comment->update();
                    }
                    unset($comments);
                    continue;
                }
                
                $comment = new net_nehmer_comments_comment();
                $comment->objectguid = $articles_by_title[$entry_title]->guid;
                $comment->author = (string) $entry->author->name;
                $comment->content = (string) $entry->content;
                $comment->metadata->published = $entry_published;
                $comment->status = $this->_config->get('buzz_initial_status');
                $comment->create();
            }
        }
        $_MIDCOM->auth->drop_sudo();

        debug_add('Done');
    }
}
