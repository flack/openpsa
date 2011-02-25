<?php
/**
 * @package net.nehmer.blog
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Blog index page handler
 *
 * @package net.nehmer.blog
 */
class net_nehmer_blog_handler_view extends midcom_baseclasses_components_handler
{
    /**
     * The content topic to use
     *
     * @var midcom_db_topic
     * @access private
     */
    private $_content_topic = null;

    /**
     * The article to display
     *
     * @var midcom_db_article
     * @access private
     */
    private $_article = null;

    /**
     * The Datamanager of the article to display.
     *
     * @var midcom_helper_datamanager2_datamanager
     * @access private
     */
    private $_datamanager = null;

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
        $this->_request_data['article'] =& $this->_article;
        $this->_request_data['datamanager'] =& $this->_datamanager;

        // Populate the toolbar
        if ($this->_article->can_do('midgard:update'))
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "edit/{$this->_article->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('edit'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'e',
                )
            );
        }

        $article = $this->_article;
        if ($this->_article->topic !== $this->_content_topic->id)
        {
            $qb = net_nehmer_blog_link_dba::new_query_builder();
            $qb->add_constraint('topic', '=', $this->_content_topic->id);
            $qb->add_constraint('article', '=', $this->_article->id);
            if ($qb->count() === 1)
            {
                // Get the link
                $results = $qb->execute_unchecked();
                $article = $results[0];
            }
        }
        if ($article->can_do('midgard:delete'))
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "delete/{$this->_article->guid}/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('delete'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                    MIDCOM_TOOLBAR_ACCESSKEY => 'd',
                )
            );
        }
    }

    /**
     * Maps the content topic from the request data to local member variables.
     */
    public function _on_initialize()
    {
        $this->_content_topic =& $this->_request_data['content_topic'];
        $this->_request_data['config'] =& $this->_config;
    }

    /**
     * Can-Handle check against the article name. We have to do this explicitly
     * in can_handle already, otherwise we would hide all subtopics as the request switch
     * accepts all argument count matches unconditionally.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean True if the request can be handled, false otherwise.
     */
    public function _can_handle_view ($handler_id, array $args, array &$data)
    {
        $qb = midcom_db_article::new_query_builder();
        net_nehmer_blog_viewer::article_qb_constraints($qb, $data, $handler_id);

        $qb->begin_group('OR');
            $qb->add_constraint('name', '=', $args[0]);
            $qb->add_constraint('guid', '=', $args[0]);
        $qb->end_group();
        $articles = $qb->execute();
        if (count($articles) > 0)
        {
            $this->_article = $articles[0];
        }

        if (!$this->_article)
        {
            return false;
            // This will 404
        }
        return true;
    }

    /**
     * Handle actual article display
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_view ($handler_id, array $args, array &$data)
    {
        if (!$this->_article)
        {
            throw new midcom_error('Failed to load article');
        }

        if ($handler_id == 'view-raw')
        {
            $_MIDCOM->skip_page_style = true;
        }

        $this->_load_datamanager();

        if ($this->_config->get('enable_ajax_editing'))
        {
            $this->_request_data['controller'] = midcom_helper_datamanager2_controller::create('ajax');
            $this->_request_data['controller']->schemadb =& $this->_request_data['schemadb'];
            $this->_request_data['controller']->set_storage($this->_article);
            $this->_request_data['controller']->process_ajax();
        }

        $tmp = Array();
        $arg = $this->_article->name ? $this->_article->name : $this->_article->guid;
        if ($this->_config->get('view_in_url'))
        {
            $view_url = "view/{$arg}/";
        }
        else
        {
            $view_url = "{$arg}/";
        }

        $this->add_breadcrumb($view_url, $this->_article->title);

        $this->_prepare_request_data();

        if (   $this->_config->get('enable_article_links')
            && $this->_content_topic->can_do('midgard:create'))
        {
            $this->_view_toolbar->add_item(
                array
                (
                    MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('article link')),
                    MIDCOM_TOOLBAR_URL => "create/link/?article={$this->_article->id}",
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/attach.png',
                )
            );
        }

        $_MIDCOM->bind_view_to_object($this->_article, $this->_datamanager->schema->name);
        $_MIDCOM->set_26_request_metadata($this->_article->metadata->revised, $this->_article->guid);
        $_MIDCOM->set_pagetitle("{$this->_topic->extra}: {$this->_article->title}");
    }

    /**
     * Internal helper, loads the datamanager for the current article. Any error triggers a 500.
     */
    private function _load_datamanager()
    {
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_request_data['schemadb']);

        if (! $this->_datamanager->autoset_storage($this->_article))
        {
            throw new midcom_error("Failed to create a DM2 instance for article {$this->_article->id}.");
        }
    }

    /**
     * Try to find a comments node (cache results)
     */
    private function _seek_comments()
    {
        if ($this->_config->get('comments_topic'))
        {
            // We have a specified photostream here
            try
            {
                $comments_topic = new midcom_db_topic($this->_config->get('comments_topic'));
            }
            catch (midcom_error $e)
            {
                return false;
            }

            // We got a topic. Make it a NAP node
            $nap = new midcom_helper_nav();
            $comments_node = $nap->get_node($comments_topic->id);

            return $comments_node;
        }

        // No comments topic specified, autoprobe
        $comments_node = midcom_helper_misc::find_node_by_component('net.nehmer.comments');

        // Cache the data
        if ($_MIDCOM->auth->request_sudo('net.nehmer.blog'))
        {
            $this->_topic->parameter('net.nehmer.blog', 'comments_topic', $comments_node[MIDCOM_NAV_GUID]);
            $_MIDCOM->auth->drop_sudo();
        }

        return $comments_node;
    }

    /**
     * Shows the loaded article.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_view ($handler_id, array &$data)
    {
        if ($this->_config->get('enable_ajax_editing'))
        {
            // For AJAX handling it is the controller that renders everything
            $this->_request_data['view_article'] = $this->_request_data['controller']->get_content_html();
        }
        else
        {
            $this->_request_data['view_article'] = $this->_datamanager->get_content_html();
        }

        if ($this->_config->get('comments_enable'))
        {
            $comments_node = $this->_seek_comments();
            if ($comments_node)
            {
                $this->_request_data['comments_url'] = $comments_node[MIDCOM_NAV_RELATIVEURL] . "comment/{$this->_article->guid}";
            }
            // TODO: Should we tell admin to create a net.nehmer.comments folder?
        }

        midcom_show_style('view');
    }
}
?>
