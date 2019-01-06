<?php
/**
 * @package net.nehmer.blog
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;

/**
 * Blog Index handler page handler
 *
 * Shows the configured number of postings with their abstracts.
 *
 * @package net.nehmer.blog
 */
class net_nehmer_blog_handler_index extends midcom_baseclasses_components_handler
{
    use net_nehmer_blog_handler;

    /**
     * The articles to display
     *
     * @var Array
     */
    private $_articles;

    /**
     * Shows the autoindex list. Nothing to do in the handle phase except setting last modified
     * dates.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_index($handler_id, array $args, array &$data)
    {
        if ($handler_id == 'ajax-latest') {
            midcom::get()->skip_page_style = true;
        }

        $data['datamanager'] = new datamanager($data['schemadb']);
        $qb = new org_openpsa_qbpager(midcom_db_article::class, 'net_nehmer_blog_index');
        $data['qb'] = $qb;
        $this->article_qb_constraints($qb);

        // Set default page title
        $data['page_title'] = $this->_topic->extra;

        // Filter by categories
        if (   $handler_id == 'index-category'
            || $handler_id == 'latest-category') {
            $data['category'] = trim(strip_tags($args[0]));

            $this->_process_category_constraint($qb);
        }

        $qb->add_order('metadata.published', 'DESC');

        if ($handler_id == 'latest' || $handler_id == 'ajax-latest') {
            $qb->results_per_page = $args[0];
        } elseif ($handler_id == 'latest-category') {
            $qb->results_per_page = $args[1];
        } else {
            $qb->results_per_page = $this->_config->get('index_entries');
        }

        $this->_articles = $qb->execute();

        midcom::get()->metadata->set_request_metadata($this->get_last_modified(), $this->_topic->guid);

        if ($qb->get_current_page() > 1) {
            $this->add_breadcrumb(
                midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX),
                sprintf($this->_i18n->get_string('page %s', 'org.openpsa.qbpager'), $qb->get_current_page())
            );
        }
    }

    private function _process_category_constraint($qb)
    {
        if (!in_array($this->_request_data['category'], $this->_request_data['categories'])) {
            if (!$this->_config->get('categories_custom_enable')) {
                throw new midcom_error('Custom categories are not allowed');
            }
            // TODO: Check here if there are actually items in this cat?
        }

        $this->apply_category_constraint($qb, $this->_request_data['category']);

        // Add category to title
        $this->_request_data['page_title'] = sprintf($this->_l10n->get('%s category %s'), $this->_topic->extra, $this->_request_data['category']);
        midcom::get()->head->set_pagetitle($this->_request_data['page_title']);

        // Activate correct leaf
        if (   $this->_config->get('show_navigation_pseudo_leaves')
            && in_array($this->_request_data['category'], $this->_request_data['categories'])) {
            $this->set_active_leaf($this->_topic->id . '_CAT_' . $this->_request_data['category']);
        }

        // Add RSS feed to headers
        if ($this->_config->get('rss_enable')) {
            midcom::get()->head->add_link_head([
                'rel'   => 'alternate',
                'type'  => 'application/rss+xml',
                'title' => $this->_l10n->get('rss 2.0 feed') . ": {$this->_request_data['category']}",
                'href'  => midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX) . "feeds/category/{$this->_request_data['category']}/",
            ]);
        }
    }

    /**
     * Displays the index page
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_index($handler_id, array &$data)
    {
        $data['index_fulltext'] = $this->_config->get('index_fulltext');

        if ($this->_config->get('ajax_comments_enable')) {
            if ($comments_node = $this->_seek_comments()) {
                $data['ajax_comments_enable'] = true;
                $data['base_ajax_comments_url'] = $comments_node[MIDCOM_NAV_RELATIVEURL] . "comment/";
            }
        }

        midcom_show_style('index-start');

        $data['comments_enable'] = (bool) $this->_config->get('comments_enable');

        if ($this->_articles) {
            $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
            $total_count = count($this->_articles);
            $data['article_count'] = $total_count;
            foreach ($this->_articles as $article_counter => $article) {
                try {
                    $data['datamanager']->set_storage($article);
                } catch (midcom_error $e) {
                    $e->log();
                    continue;
                }

                $data['article'] = $article;
                $data['article_counter'] = $article_counter;

                $data['local_view_url'] = $prefix . $this->get_url($article);
                $data['view_url'] = $this->get_url($article, true);
                if (!preg_match('/^http(s):\/\//', $data['view_url'])) {
                    $data['view_url'] = $prefix . $data['view_url'];
                }
                $data['linked'] = ($article->topic !== $this->_topic->id);
                if ($data['linked']) {
                    $nap = new midcom_helper_nav();
                    $data['node'] = $nap->get_node($article->topic);
                }

                midcom_show_style('index-item');
            }
        } else {
            midcom_show_style('index-empty');
        }

        midcom_show_style('index-end');
    }

    // helpers follow
    /**
     * Try to find a comments node (cache results)
     */
    private function _seek_comments()
    {
        if ($this->_config->get('comments_topic')) {
            try {
                $comments_topic = new midcom_db_topic($this->_config->get('comments_topic'));
            } catch (midcom_error $e) {
                return false;
            }

            // We got a topic. Make it a NAP node
            $nap = new midcom_helper_nav();
            return $nap->get_node($comments_topic->id);
        }

        // No comments topic specified, autoprobe
        $comments_node = midcom_helper_misc::find_node_by_component('net.nehmer.comments');

        // Cache the data
        if (midcom::get()->auth->request_sudo('net.nehmer.blog')) {
            $this->_topic->set_parameter('net.nehmer.blog', 'comments_topic', $comments_node[MIDCOM_NAV_GUID]);
            midcom::get()->auth->drop_sudo();
        }

        return $comments_node;
    }
}
