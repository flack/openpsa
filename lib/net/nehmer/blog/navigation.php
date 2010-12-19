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
    /**
     * The topic in which to look for articles. This defaults to the current content topic
     * unless overridden by the symlink topic feature.
     *
     * @var midcom_db_topic
     * @access private
     */
    private $_content_topic = null;

    /**
     * Returns a static leaf list with access to the archive.
     */
    function get_leaves()
    {
        // Check for symlink
        if (!$this->_content_topic)
        {
            $this->_determine_content_topic();
        }

        $leaves = array();

        if (!$this->_content_topic->guid)
        {
            // Safety in case symlink is broken
            return $leaves;
        }

        if ($this->_config->get('show_navigation_pseudo_leaves'))
        {
            $this->_add_pseudo_leaves($leaves);
        }

        if ($this->_config->get('show_latest_in_navigation'))
        {
            $this->_add_article_leaves();
        }
        return $leaves;
    }

    private function _add_article_leaves(&$leaves)
    {
        $qb = midcom_db_article::new_query_builder();

        // Hide the articles that have the publish time in the future and if
        // the user is not administrator
        if (   $this->_config->get('enable_scheduled_publishing')
            && !$_MIDCOM->auth->admin)
        {
            // Show the article only if the publishing time has passed or the viewer
            // is the author
            $qb->begin_group('OR');
                $qb->add_constraint('metadata.published', '<', gmdate('Y-m-d H:i:s'));

                if (   $_MIDCOM->auth->user
                    && isset($_MIDCOM->auth->user->guid))
                {
                    $qb->add_constraint('metadata.authors', 'LIKE', '|' . $_MIDCOM->auth->user->guid . '|');
                }
            $qb->end_group();
        }

        if (!$this->_config->get('enable_article_links'))
        {
            $qb->add_constraint('topic', '=', $this->_content_topic->id);
            $qb->add_constraint('up', '=', 0);

            $qb->add_order('metadata.published', 'DESC');
            $qb->set_limit((int) $this->_config->get('index_entries'));

            $results = $qb->execute();
        }
        else
        {
            // Amount of articles needed
            $limit = (int) $this->_config->get('index_entries');

            $results = array();
            $offset = 0;

            self::_get_linked_articles($this->_content_topic->id, $offset, $limit, $results);
        }

        // Checkup for the url prefix
        if ($this->_config->get('view_in_url'))
        {
            $prefix = 'view/';
        }
        else
        {
            $prefix = '';
        }

        foreach ($results as $article)
        {
            $leaves[$article->id] = array
            (
                MIDCOM_NAV_URL => "{$prefix}{$article->name}/",
                MIDCOM_NAV_NAME => ($article->title != '') ? $article->title : $article->name,
                MIDCOM_NAV_GUID => $article->guid,
                MIDCOM_NAV_OBJECT => $article,
            );
        }
    }

    private function _add_pseudo_leaves(&$leaves)
    {
        if (   $this->_config->get('archive_enable')
            && $this->_config->get('archive_in_navigation'))
        {
            $leaves["{$this->_topic->id}_ARCHIVE"] = array
            (
                MIDCOM_NAV_URL => "archive/",
                MIDCOM_NAV_NAME => $this->_l10n->get('archive'),
            );
        }
        if (   $this->_config->get('rss_enable')
            && $this->_config->get('feeds_in_navigation'))
        {
            $leaves[NET_NEHMER_BLOG_LEAFID_FEEDS] = array
            (
                MIDCOM_NAV_URL => "feeds/",
                MIDCOM_NAV_NAME => $this->_l10n->get('available feeds'),
            );
        }

        if (   $this->_config->get('categories_in_navigation')
            && $this->_config->get('categories') != '')
        {
            $categories = explode(',', $this->_config->get('categories'));
            foreach ($categories as $category)
            {
                $leaves["{$this->_topic->id}_CAT_{$category}"] = array
                (
                    MIDCOM_NAV_URL => "category/{$category}/",
                    MIDCOM_NAV_NAME => $category,
                );
            }
        }

        if (   $this->_config->get('archive_years_in_navigation')
            && $this->_config->get('archive_years_enable'))
        {
            $qb = midcom_db_article::new_query_builder();
            $qb->add_constraint('topic', '=', $this->_content_topic->id);

            // Hide the articles that have the publish time in the future and if
            // the user is not administrator
            if (   $this->_config->get('enable_scheduled_publishing')
                && !$_MIDCOM->auth->admin)
            {
                // Show the article only if the publishing time has passed or the viewer
                // is the author
                $qb->begin_group('OR');
                    $qb->add_constraint('metadata.published', '<', gmdate('Y-m-d H:i:s'));

                    if (   $_MIDCOM->auth->user
                        && isset($_MIDCOM->auth->user->guid))
                    {
                        $qb->add_constraint('metadata.authors', 'LIKE', '|' . $_MIDCOM->auth->user->guid . '|');
                    }
                $qb->end_group();
            }

            $qb->add_order('metadata.published');
            $qb->set_limit(1);
            $result = $qb->execute_unchecked();

            if (count($result) == 0)
            {
                return $leaves;
            }

            $first_year = (int) gmdate('Y', (int) $result[0]->metadata->published);
            $year = $first_year;
            $this_year = (int) gmdate('Y', time());
            while ($year <= $this_year)
            {
                $leaves["{$this->_topic->id}_ARCHIVE_{$year}"] = array
                (
                    MIDCOM_NAV_URL => "archive/year/{$year}/",
                    MIDCOM_NAV_NAME => $year,
                );
                $year = $year + 1;
            }
            $leaves = array_reverse($leaves);
        }
    }

    /**
     * Helper for fetching enough of articles
     *
     * @param int $topic_id     ID of the content topic
     * @param int $offset       Offset for the query
     * @param int $limit        How many results should be returned
     * @param array &$results   Result set
     * @return Array            Containing results
     */
    private static function _get_linked_articles($topic_id, $offset, $limit, &$results)
    {
        $mc = net_nehmer_blog_link_dba::new_collector('topic', $topic_id);
        $mc->add_value_property('article');
        $mc->add_constraint('topic', '=', $topic_id);
        $mc->add_order('metadata.published', 'DESC');
        $mc->set_offset($offset);

        // Double the limit, a sophisticated guess that there might be missing articles
        // and this should include enough of articles for us
        $mc->set_limit($limit * 2);

        // Get the results
        $mc->execute();

        $links = $mc->list_keys();

        // Return the empty result set
        if (   !is_array($links)
            || count($links) === 0)
        {
            return $results;
        }

        $i = 0;

        foreach ($links as $guid => $link)
        {
            $id = $mc->get_subkey($guid, 'article');

            $article = new midcom_db_article($id);

            // If the article was not found, it is probably due to
            if (   !isset($article)
                || !isset($article->guid)
                || !$article->guid)
            {
                continue;
            }

            $results[$id] = $article;
            $i++;

            // Break when we have enough of articles
            if ($i >= $limit)
            {
                break;
            }
        }

        // Quit the function if there is no possibility for more matches
        if (count($links) < $limit)
        {
            return $results;
        }

        // Push the offset
        $offset = $offset + $limit;

        self::_get_linked_articles($topic_id, $offset, $limit, $results);
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
     *
     * @access protected
     */
    private function _determine_content_topic()
    {
        $guid = $this->_config->get('symlink_topic');
        if (is_null($guid))
        {
            // No symlink topic
            // Workaround, we should talk to a DBA object automatically here in fact.
            $this->_content_topic = midcom_db_topic::get_cached($this->_topic->id);
            return;
        }

        $this->_content_topic = midcom_db_topic::get_cached($guid);

        if (! $this->_content_topic)
        {
            throw new midcom_error('Failed to open symlink content topic. Last Midgard Error: ' . midcom_connection::get_error_string());
        }
    }
}
?>
