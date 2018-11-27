<?php
/**
 * @package net.nehmer.blog
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Handler addons
 *
 * @package net.nehmer.blog
 */
trait net_nehmer_blog_handler
{
    public function get_url(midcom_db_article $article, $allow_external = false)
    {
        if (   $allow_external
            && $this->_config->get('link_to_external_url')
            && !empty($article->url)) {
            return $article->url;
        }

        $view_url = $article->name ?: $article->guid;

        if ($this->_config->get('view_in_url')) {
            $view_url = 'view/' . $view_url;
        }
        return $view_url . '/';
    }

    /**
     * Simple helper, gets the last modified timestamp of the topic combination
     * specified.
     */
    public function get_last_modified()
    {
        // Get last modified timestamp
        $qb = midcom_db_article::new_query_builder();
        $this->article_qb_constraints($qb);
        $qb->add_order('metadata.revised', 'DESC');
        $qb->set_limit(1);

        $articles = $qb->execute();

        if (array_key_exists(0, $articles)) {
            return max($this->_topic->metadata->revised, $articles[0]->metadata->revised);
        }
        return $this->_topic->metadata->revised;
    }

    /**
     * Sets the constraints for QB for articles
     *
     * @param midcom_core_querybuilder $qb The QB object
     */
    public function article_qb_constraints($qb)
    {
        $topic_guids = [$this->_topic->guid];

        // Resolve any other topics we may need
        if ($list_from_folders = $this->_config->get('list_from_folders')) {
            // We have specific folders to list from, therefore list from them and current node
            $guids = explode('|', $list_from_folders);
            $topic_guids = array_merge($topic_guids, array_filter($guids, 'mgd_is_guid'));
        }

        $qb->add_constraint('topic.guid', 'IN', $topic_guids);

        if (   count($topic_guids) > 1
            && $list_from_folders_categories = $this->_config->get('list_from_folders_categories')) {
            $list_from_folders_categories = explode(',', $list_from_folders_categories);

            $qb->begin_group('OR');
            $qb->add_constraint('topic.guid', '=', $topic_guids[0]);
            $qb->begin_group('OR');
            foreach ($list_from_folders_categories as $category) {
                $this->apply_category_constraint($qb, $category);
            }
            $qb->end_group();
            $qb->end_group();
        }

        // Hide the articles that have the publish time in the future and if
        // the user is not administrator
        if (   $this->_config->get('enable_scheduled_publishing')
            && !midcom::get()->auth->admin) {
            // Show the article only if the publishing time has passed or the viewer
            // is the author
            $qb->begin_group('OR');
            $qb->add_constraint('metadata.published', '<', gmdate('Y-m-d H:i:s'));

            if (!empty(midcom::get()->auth->user->guid)) {
                $qb->add_constraint('metadata.authors', 'LIKE', '|' . midcom::get()->auth->user->guid . '|');
            }
            $qb->end_group();
        }

        $qb->add_constraint('up', '=', 0);
    }

    /**
     * @param midgard_query_builder $qb
     * @param string $category
     */
    public function apply_category_constraint($qb, $category)
    {
        if ($category = trim($category)) {
            $qb->begin_group('OR');
            $qb->add_constraint('extra1', 'LIKE', "%|{$category}|%");
            $qb->add_constraint('extra1', '=', $category);
            $qb->end_group();
        }
    }
}