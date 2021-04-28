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
    use net_nehmer_blog_handler;

    /**
     * @var midcom_db_article
     */
    private $_article;

    /**
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
     * Handle actual article display
     */
    public function _handler_view(string $handler_id, array $args, array &$data)
    {
        $qb = midcom_db_article::new_query_builder();
        $this->article_qb_constraints($qb);

        $qb->begin_group('OR');
        $qb->add_constraint('name', '=', $args[0]);
        $qb->add_constraint('guid', '=', $args[0]);
        $qb->end_group();
        $this->_article = $qb->get_result(0);
        if (!$this->_article) {
            throw new midcom_error_notfound('Could not find ' . $args[0]);
        }

        if ($handler_id == 'view-raw') {
            midcom::get()->skip_page_style = true;
        }

        $this->_datamanager = new datamanager($data['schemadb']);
        $this->_datamanager->set_storage($this->_article);

        if ($this->_config->get('comments_enable')) {
            if ($node = net_nehmer_comments_interface::get_node($this->_topic, $this->_config->get('comments_topic'))) {
                $data['comments_url'] = $node[MIDCOM_NAV_RELATIVEURL] . "comment/{$this->_article->guid}";
                if (   $this->_topic->can_do('midgard:update')
                    && $this->_topic->can_do('net.nehmer.comments:moderation')) {
                    net_nehmer_comments_viewer::add_head_elements();
                }
            }
            // TODO: Should we tell admin to create a net.nehmer.comments folder?
        }

        $this->add_breadcrumb($this->get_url($this->_article), $this->_article->title);

        $this->_prepare_request_data();

        $this->bind_view_to_object($this->_article, $this->_datamanager->get_schema()->get_name());
        midcom::get()->metadata->set_request_metadata($this->_article->metadata->revised, $this->_article->guid);
        midcom::get()->head->set_pagetitle("{$this->_topic->extra}: {$this->_article->title}");
        $data['view_article'] = $this->_datamanager->get_content_html();
        return $this->show('view');
    }
}
