<?php
/**
 * @package net.nehmer.comments
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Cron handler for fetching comments from Atom-comments feeds
 *
 * @package net.nehmer.comments
 */
class net_nehmer_comments_cron_atom extends midcom_baseclasses_components_cron_handler
{
    public function execute()
    {
        if (!$this->_config->get('atom_comments_import_enable')) {
            debug_add('Import of Atom comment feeds disabled, aborting', MIDCOM_LOG_INFO);
            return;
        }

        if (!midcom::get()->auth->request_sudo('net.nehmer.comments')) {
            debug_add('Could not get sudo, aborting operation', MIDCOM_LOG_ERROR);
            return;
        }

        // Get 50 latest articles so we can look for those
        $qb = midcom_db_article::new_query_builder();
        $qb->add_constraint('topic.guid', '=', $this->_config->get('atom_comments_topic'));
        $qb->add_order('metadata.published', 'DESC');
        $qb->set_limit(50);

        foreach ($qb->execute() as $article) {
            $replies_url = $article->get_parameter('net.nemein.rss', 'replies_url');

            if (empty($replies_url)) {
                // no replies-url for this article. skipping
                continue;
            }

            // fetch and parse Feed from URL
            $comments = net_nemein_rss_fetch::raw_fetch($replies_url)->get_items();

            foreach ($comments as $comment) {
                $qb = net_nehmer_comments_comment::new_query_builder();
                $qb->add_constraint('remoteid', '=', $comment->get_id());
                $db_comments = $qb->execute();

                if (!empty($db_comments)) {
                    $db_comment = $db_comments[0];

                    $db_comment->title = $comment->get_title();
                    $db_comment->content = $comment->get_description();
                    $db_comment->update();
                } else {
                    $author_info = net_nemein_rss_fetch::parse_item_author($comment);

                    $db_comment = new net_nehmer_comments_comment();
                    $db_comment->objectguid = $article->guid;
                    $db_comment->metadata->published = $comment->get_date('Y-m-d H:i:s');
                    $db_comment->author = $author_info['full_name'] ?? $author_info['username'];
                    $db_comment->status = $this->_config->get('atom_comments_initial_status');

                    $db_comment->remoteid = $comment->get_id();
                    $db_comment->title = $comment->get_title();
                    $db_comment->content = $comment->get_description();

                    $db_comment->create();
                }
            } // <-- comments
        } // <-- articles

        midcom::get()->auth->drop_sudo();
    }
}
