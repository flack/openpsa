<?php
/**
 * @package net.nehmer.blog
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Blog navigation interface class
 *
 * See the individual member documentations about special NAP options in use.
 *
 * @package net.nehmer.blog
 */

class net_nehmer_blog_navigation extends midcom_baseclasses_components_navigation
{
    const LEAFID_FEEDS = 2;

    /**
     * The topic in which to look for articles. This defaults to the current content topic
     * unless overridden by the symlink topic feature.
     *
     * @var midcom_db_topic
     */
    private $_content_topic = null;

    /**
     * Returns a static leaf list with access to the archive.
     */
    public function get_leaves()
    {
        $leaves = array();

        if (!$this->_content_topic->guid) {
            // Safety in case symlink is broken
            return $leaves;
        }

        if ($this->_config->get('show_navigation_pseudo_leaves')) {
            $this->_add_pseudo_leaves($leaves);
        }

        if ($this->_config->get('show_latest_in_navigation')) {
            $this->_add_article_leaves($leaves);
        }
        return $leaves;
    }

    private function _add_article_leaves(array &$leaves)
    {
        $qb = midcom_db_article::new_query_builder();

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

        if (!$this->_config->get('enable_article_links')) {
            $qb->add_constraint('topic', '=', $this->_content_topic->id);
            $qb->add_constraint('up', '=', 0);
        } else {
            $mc = net_nehmer_blog_link_dba::new_collector('topic', $this->_content_topic->id);
            $qb->begin_group('OR');
            $qb->add_constraint('id', 'IN', $mc->get_values('article'));
            $qb->add_constraint('topic', '=', $this->_content_topic->id);
            $qb->end_group();
        }
        $qb->add_order('metadata.published', 'DESC');
        $qb->set_limit((int) $this->_config->get('index_entries'));

        $results = $qb->execute();

        foreach ($results as $article) {
            $leaves[$article->id] = array(
                MIDCOM_NAV_URL => $this->_get_url($article),
                MIDCOM_NAV_NAME => $article->title ?: $article->name,
                MIDCOM_NAV_GUID => $article->guid,
                MIDCOM_NAV_OBJECT => $article,
            );
        }
    }

    private function _get_url(midcom_db_article $article)
    {
        $view_url = $article->name ?: $article->guid;

        if ($this->_config->get('view_in_url')) {
            $view_url = 'view/' . $view_url;
        }
        return $view_url . '/';
    }

    private function _add_pseudo_leaves(array &$leaves)
    {
        if (   $this->_config->get('archive_enable')
            && $this->_config->get('archive_in_navigation')) {
            $leaves["{$this->_topic->id}_ARCHIVE"] = array(
                MIDCOM_NAV_URL => "archive/",
                MIDCOM_NAV_NAME => $this->_l10n->get('archive'),
            );
        }
        if (   $this->_config->get('rss_enable')
            && $this->_config->get('feeds_in_navigation')) {
            $leaves[self::LEAFID_FEEDS] = array(
                MIDCOM_NAV_URL => "feeds/",
                MIDCOM_NAV_NAME => $this->_l10n->get('available feeds'),
            );
        }

        if (   $this->_config->get('categories_in_navigation')
            && $this->_config->get('categories') != '') {
            $categories = explode(',', $this->_config->get('categories'));
            foreach ($categories as $category) {
                $leaves["{$this->_topic->id}_CAT_{$category}"] = array(
                    MIDCOM_NAV_URL => "category/{$category}/",
                    MIDCOM_NAV_NAME => $category,
                );
            }
        }

        if (   $this->_config->get('archive_years_in_navigation')
            && $this->_config->get('archive_years_enable')) {
            $qb = midcom_db_article::new_query_builder();
            $qb->add_constraint('topic', '=', $this->_content_topic->id);

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

            $qb->add_order('metadata.published');
            $qb->set_limit(1);
            $result = $qb->execute_unchecked();

            if (count($result) == 0) {
                return $leaves;
            }

            $first_year = (int) gmdate('Y', (int) $result[0]->metadata->published);
            $year = $first_year;
            $this_year = (int) gmdate('Y', time());
            while ($year <= $this_year) {
                $leaves["{$this->_topic->id}_ARCHIVE_{$year}"] = array(
                    MIDCOM_NAV_URL => "archive/year/{$year}/",
                    MIDCOM_NAV_NAME => $year,
                );
                $year = $year + 1;
            }
            $leaves = array_reverse($leaves);
        }
    }

    /**
     * This event handler will determine the content topic, which might differ due to a
     * set content symlink.
     */
    public function _on_set_object()
    {
        $this->_determine_content_topic();
        return true;
    }

    /**
     * Set the content topic to use. This will check against the configuration setting 'symlink_topic'.
     * We don't do sanity checking here for performance reasons, it is done when accessing the topic,
     * that should be enough.
     */
    private function _determine_content_topic()
    {
        $guid = $this->_config->get('symlink_topic');
        if (is_null($guid)) {
            // No symlink topic
            // Workaround, we should talk to a DBA object automatically here in fact.
            $this->_content_topic = midcom_db_topic::get_cached($this->_topic->id);
            return;
        }

        $this->_content_topic = midcom_db_topic::get_cached($guid);
    }
}
