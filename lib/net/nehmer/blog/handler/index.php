<?php
/**
 * @package net.nehmer.blog
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: index.php 26359 2010-06-13 21:05:01Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Blog Index handler page handler
 *
 * Shows the configured number of postings with their abstracts.
 *
 * @package net.nehmer.blog
 */

class net_nehmer_blog_handler_index extends midcom_baseclasses_components_handler
{
    /**
     * The content topic to use
     *
     * @var midcom_db_topic
     * @access private
     */
    var $_content_topic = null;

    /**
     * The articles to display
     *
     * @var Array
     * @access private
     */
    var $_articles = null;

    /**
     * The datamanager for the currently displayed article.
     *
     * @var midcom_helper_datamanager2_datamanager
     */
    var $_datamanager = null;

    /**
     * Simple default constructor.
     */
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Maps the content topic from the request data to local member variables.
     */
    function _on_initialize()
    {
        $this->_content_topic =& $this->_request_data['content_topic'];
        $this->_request_data['config'] =& $this->_config;

        $_MIDCOM->load_library('org.openpsa.qbpager');
    }

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    function _prepare_request_data()
    {
        $this->_request_data['datamanager'] =& $this->_datamanager;
    }


    /**
     * Shows the autoindex list. Nothing to do in the handle phase except setting last modified
     * dates.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_index ($handler_id, $args, &$data)
    {
        if ($handler_id == 'ajax-latest')
        {
            $_MIDCOM->skip_page_style = true;
        }

        $this->_datamanager = new midcom_helper_datamanager2_datamanager($data['schemadb']);
        $qb = new org_openpsa_qbpager('midcom_db_article', 'net_nehmer_blog_index');
        $data['qb'] =& $qb;
        net_nehmer_blog_viewer::article_qb_constraints($qb, $data, $handler_id);

        // Set default page title
        $data['page_title'] = $this->_topic->extra;

        // Filter by categories
        if (   $handler_id == 'index-category'
            || $handler_id == 'latest-category')
        {
            $data['category'] = trim(strip_tags($args[0]));
            if (!in_array($data['category'], $data['categories']))
            {
                // This is not a predefined category from configuration, check if site maintainer allows us to show it
                if (!$this->_config->get('categories_custom_enable'))
                {
                    return false;
                }
                // TODO: Check here if there are actually items in this cat?
            }

            // TODO: check schema storage to get fieldname
            $multiple_categories = true;
            if (   isset($data['schemadb']['default'])
                && isset($data['schemadb']['default']->fields['categories'])
                && array_key_exists('allow_multiple', $data['schemadb']['default']->fields['categories']['type_config'])
                && !$data['schemadb']['default']->fields['categories']['type_config']['allow_multiple'])
            {
                $multiple_categories = false;
            }
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("multiple_categories={$multiple_categories}");
            debug_pop();
            if ($multiple_categories)
            {
                $qb->add_constraint('extra1', 'LIKE', "%|{$this->_request_data['category']}|%");
            }
            else
            {
                $qb->add_constraint('extra1', '=', (string) $data['category']);
            }

            // Add category to title
            $data['page_title'] = sprintf($this->_l10n->get('%s category %s'), $this->_topic->extra, $data['category']);
            $_MIDCOM->set_pagetitle($data['page_title']);

            // Activate correct leaf
            if (   $this->_config->get('show_navigation_pseudo_leaves')
                && in_array($data['category'], $data['categories']))
            {
                $this->_component_data['active_leaf'] = "{$this->_topic->id}_CAT_{$data['category']}";
            }

            // Add RSS feed to headers
            if ($this->_config->get('rss_enable'))
            {
                $_MIDCOM->add_link_head
                (
                    array
                    (
                        'rel'   => 'alternate',
                        'type'  => 'application/rss+xml',
                        'title' => $this->_l10n->get('rss 2.0 feed') . ": {$data['category']}",
                        'href'  => $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . "feeds/category/{$data['category']}/",
                    )
                );
            }
        }

        $qb->add_order('metadata.published', 'DESC');

        switch ($handler_id)
        {
            case 'index':
            case 'index-category':
                $qb->results_per_page = $this->_config->get('index_entries');
                break;

            case 'latest':
            case 'ajax-latest':
                $qb->results_per_page = $args[0];
                break;

            case 'latest-category':
                $qb->results_per_page = $args[1];
                break;

            default:
                $qb->results_per_page = $this->_config->get('index_entries');
                break;
        }

        $this->_articles = $qb->execute();

        $this->_prepare_request_data();
        $_MIDCOM->set_26_request_metadata(net_nehmer_blog_viewer::get_last_modified($this->_topic, $this->_content_topic), $this->_topic->guid);

        if ($qb->get_current_page() > 1)
        {
            $tmp[] = Array
            (
                MIDCOM_NAV_URL => $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX),
                MIDCOM_NAV_NAME => sprintf($_MIDCOM->i18n->get_string('page %s', 'org.openpsa.qbpager'), $qb->get_current_page()),
            );
            $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);
        }

        return true;
    }

    /**
     * Displays the index page
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_index($handler_id, &$data)
    {
        $data['index_fulltext'] = $this->_config->get('index_fulltext');

        if ($this->_config->get('ajax_comments_enable'))
        {
            $_MIDCOM->componentloader->load('net.nehmer.comments');

            $comments_node = $this->_seek_comments();

            if ($comments_node)
            {
                $this->_request_data['ajax_comments_enable'] = true;
                $this->_request_data['base_ajax_comments_url'] = $comments_node[MIDCOM_NAV_RELATIVEURL] . "comment/";
            }
        }

        midcom_show_style('index-start');

        if ($this->_config->get('comments_enable'))
        {
            $_MIDCOM->componentloader->load('net.nehmer.comments');
            $this->_request_data['comments_enable'] = true;
        }

        if ($this->_articles)
        {
            $total_count = count($this->_articles);
            $data['article_count'] = $total_count;
            $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
            foreach ($this->_articles as $article_counter => $article)
            {
                if (! $this->_datamanager->autoset_storage($article))
                {
                    debug_push_class(__CLASS__, __FUNCTION__);
                    debug_add("The datamanager for article {$article->id} could not be initialized, skipping it.");
                    debug_print_r('Object was:', $article);
                    debug_pop();
                    continue;
                }

                $data['article'] =& $article;
                $data['article_counter'] = $article_counter;
                $arg = $article->name ? $article->name : $article->guid;

                if ($this->_config->get('view_in_url'))
                {
                    $data['local_view_url'] = "{$prefix}view/{$arg}/";
                }
                else
                {
                    $data['local_view_url'] = "{$prefix}{$arg}/";
                }

                if (   $this->_config->get('link_to_external_url')
                    && !empty($article->url))
                {
                    $data['view_url'] = $article->url;
                }
                else
                {
                    $data['view_url'] = $data['local_view_url'];
                }

                if ($article->topic === $this->_content_topic->id)
                {
                    $data['linked'] = false;
                }
                else
                {
                    $data['linked'] = true;

                    $nap = new midcom_helper_nav();
                    $data['node'] = $nap->get_node($article->topic);
                }

                midcom_show_style('index-item', array($article->guid));
            }
        }
        else
        {
            midcom_show_style('index-empty');
        }

        midcom_show_style('index-end');
        return true;
    }

    // helpers follow
    /**
     * Try to find a comments node (cache results)
     *
     * @access private
     */
    private function _seek_comments()
    {
        if ($this->_config->get('comments_topic'))
        {
            // We have a specified photostream here
            $comments_topic = new midcom_db_topic($this->_config->get('comments_topic'));
            if (   !is_object($comments_topic)
                || !isset($comments_topic->guid)
                || empty($comments_topic->guid))
            {
                return false;
            }

            // We got a topic. Make it a NAP node
            $nap = new midcom_helper_nav();
            $comments_node = $nap->get_node($comments_topic->id);

            return $comments_node;
        }

        // No comments topic specified, autoprobe
        $comments_node = midcom_helper_find_node_by_component('net.nehmer.comments');

        // Cache the data
        if ($_MIDCOM->auth->request_sudo('net.nehmer.blog'))
        {
            $this->_topic->parameter('net.nehmer.blog', 'comments_topic', $comments_node[MIDCOM_NAV_GUID]);
            $_MIDCOM->auth->drop_sudo();
        }

        return $comments_node;
    }
}
?>