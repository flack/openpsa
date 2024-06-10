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
    public function get_url(midcom_db_article $article, bool $allow_external = false) : string
    {
        if (   $allow_external
            && $this->_config->get('link_to_external_url')
            && !empty($article->url)) {
            return $article->url;
        }

        return ($article->name ?: $article->guid) . '/';
    }

    /**
     * Simple helper, gets the last modified timestamp of the topic combination
     * specified.
     */
    public function get_last_modified() : int
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
     */
    public function article_qb_constraints(midcom_core_querybuilder $qb)
    {
        $topic_guids = [$this->_topic->guid];

        // Resolve any other topics we may need
        if ($list_from_folders = $this->_config->get('list_from_folders')) {
            // We have specific folders to list from, therefore list from them and current node
            $guids = explode('|', $list_from_folders);
            $topic_guids = array_merge($topic_guids, array_filter($guids, mgd_is_guid(...)));
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

        net_nehmer_blog_navigation::add_scheduling_constraints($qb, $this->_config);
        $qb->add_constraint('up', '=', 0);
    }

    public function apply_category_constraint(midcom_core_querybuilder $qb, string $category)
    {
        if ($category = trim($category)) {
            $qb->begin_group('OR');
            $qb->add_constraint('extra1', 'LIKE', "%|{$category}|%");
            $qb->add_constraint('extra1', '=', $category);
            $qb->end_group();
        }
    }
}