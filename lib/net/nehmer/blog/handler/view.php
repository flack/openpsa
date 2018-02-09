<?php
/**
 * @package net.nehmer.blog
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;

/**
 * Blog index page handler
 *
 * @package net.nehmer.blog
 */
class net_nehmer_blog_handler_view extends midcom_baseclasses_components_handler
{
    /**
     * The article to display
     *
     * @var midcom_db_article
     */
    private $_article;

    /**
     * The Datamanager of the article to display.
     *
     * @var datamanager
     */
    private $_datamanager;

    /**
     * Simple helper which references all important members to the request data listing
     * for usage within the style listing.
     */
    private function _prepare_request_data()
    {
        $this->_request_data['article'] = $this->_article;
        $this->_request_data['datamanager'] = $this->_datamanager;

        $buttons = [];
        $workflow = $this->get_workflow('datamanager');
        if ($this->_article->can_do('midgard:update')) {
            $buttons[] = $workflow->get_button("edit/{$this->_article->guid}/", [
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            ]);
        }

        if (   $this->_article->topic === $this->_topic->id
            && $this->_article->can_do('midgard:delete')) {
            $delete = $this->get_workflow('delete', ['object' => $this->_article]);
            $buttons[] = $delete->get_button("delete/{$this->_article->guid}/");
        }
        $this->_view_toolbar->add_items($buttons);
    }

    /**
     * Can-Handle check against the article name. We have to do this explicitly
     * in can_handle already, otherwise we would hide all subtopics as the request switch
     * accepts all argument count matches unconditionally.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     * @return boolean True if the request can be handled, false otherwise.
     */
    public function _can_handle_view($handler_id, array $args, array &$data)
    {
        $qb = midcom_db_article::new_query_builder();
        $this->_master->article_qb_constraints($qb, $handler_id);

        $qb->begin_group('OR');
        $qb->add_constraint('name', '=', $args[0]);
        $qb->add_constraint('guid', '=', $args[0]);
        $qb->end_group();
        if ($this->_article = $qb->get_result(0)) {
            return true;
        }

        return false;
    }

    /**
     * Handle actual article display
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_view($handler_id, array $args, array &$data)
    {
        if ($handler_id == 'view-raw') {
            midcom::get()->skip_page_style = true;
        }

        $this->_datamanager = new datamanager($data['schemadb']);
        $this->_datamanager->set_storage($this->_article);

        if ($this->_config->get('comments_enable')) {
            if ($comments_node = $this->_seek_comments()) {
                $this->_request_data['comments_url'] = $comments_node[MIDCOM_NAV_RELATIVEURL] . "comment/{$this->_article->guid}";
                if (   $this->_topic->can_do('midgard:update')
                    && $this->_topic->can_do('net.nehmer.comments:moderation')) {
                    net_nehmer_comments_viewer::add_head_elements();
                }
            }
            // TODO: Should we tell admin to create a net.nehmer.comments folder?
        }

        $this->add_breadcrumb($this->_master->get_url($this->_article), $this->_article->title);

        $this->_prepare_request_data();

        $this->bind_view_to_object($this->_article, $this->_datamanager->get_schema()->get_name());
        midcom::get()->metadata->set_request_metadata($this->_article->metadata->revised, $this->_article->guid);
        midcom::get()->head->set_pagetitle("{$this->_topic->extra}: {$this->_article->title}");
    }

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

    /**
     * Shows the loaded article.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_view($handler_id, array &$data)
    {
        $this->_request_data['view_article'] = $this->_datamanager->get_content_html();
        midcom_show_style('view');
    }
}
