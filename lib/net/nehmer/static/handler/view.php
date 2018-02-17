<?php
/**
 * @package net.nehmer.static
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;

/**
 * n.n.static index page handler
 *
 * @package net.nehmer.static
 */
class net_nehmer_static_handler_view extends midcom_baseclasses_components_handler
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
     * Not applicable for the "index" handler, where the article name is fixed (see handle).
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     * @return boolean True if the request can be handled, false otherwise.
     */
    public function _can_handle_view($handler_id, array $args, array &$data)
    {
        if ($handler_id == 'index') {
            return true;
        }

        $qb = net_nehmer_static_viewer::get_topic_qb($this->_config, $this->_topic->id);
        $qb->add_constraint('name', '=', $args[0]);
        $qb->add_constraint('up', '=', 0);
        $qb->set_limit(1);

        if ($this->_article = $qb->get_result(0)) {
            return true;
        }

        return false;
    }

    /**
     * Looks up an article to display. If the handler_id is 'index', the index article is tried to be
     * looked up, otherwise the article name is taken from args[0]. Triggered error messages are
     * generated accordingly. A missing index will trigger a forbidden error, a missing regular
     * article a 404 (from can_handle).
     *
     * Note, that the article for non-index mode operation is automatically determined in the can_handle
     * phase.
     *
     * If create privileges apply, we relocate to the index creation article
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_view($handler_id, array $args, array &$data)
    {
        if ($handler_id == 'index') {
            $this->_load_index_article();
        }

        if ($handler_id == 'view_raw') {
            midcom::get()->skip_page_style = true;
        }

        $this->_datamanager = new datamanager($this->_request_data['schemadb']);
        $this->_datamanager->set_storage($this->_article);

        $arg = $this->_article->name ?: $this->_article->guid;
        if (   $arg != 'index'
            && $this->_config->get('hide_navigation')) {
            $this->add_breadcrumb("{$arg}/", $this->_article->title);
        }

        $this->_prepare_request_data();

        midcom::get()->metadata->set_request_metadata($this->_article->metadata->revised, $this->_article->guid);
        $this->bind_view_to_object($this->_article, $this->_datamanager->get_schema()->get_name());

        if (   $this->_config->get('indexinnav')
            || $this->_config->get('autoindex')
            || $this->_article->name != 'index') {
            $this->set_active_leaf($this->_article->id);
        }

        if (   $this->_config->get('folder_in_title')
            && $this->_topic->extra != $this->_article->title) {
            midcom::get()->head->set_pagetitle("{$this->_topic->extra}: {$this->_article->title}");
        } else {
            midcom::get()->head->set_pagetitle($this->_article->title);
        }
        $data['view_article'] = $data['datamanager']->get_content_html();
        return $this->show('show-article');
    }

    private function _load_index_article()
    {
        $qb = net_nehmer_static_viewer::get_topic_qb($this->_config, $this->_topic->id);
        $qb->add_constraint('name', '=', 'index');
        $qb->set_limit(1);
        $this->_article = $qb->get_result(0);

        if (empty($this->_article)) {
            if ($this->_topic->can_do('midgard:create')) {
                // Check via non-ACLd QB that the topic really doesn't have index article before relocating
                $index_qb = midcom_db_article::new_query_builder();
                $index_qb->add_constraint('topic', '=', $this->_topic->id);
                $index_qb->add_constraint('name', '=', 'index');
                if ($index_qb->count_unchecked() == 0) {
                    $schema = $this->_request_data['schemadb']->get_first();
                    midcom::get()->relocate("createindex/{$schema->get_name()}/");
                    // This will exit.
                }
            }

            throw new midcom_error_forbidden('Directory index forbidden');
        }
    }
}
