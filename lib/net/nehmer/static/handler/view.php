<?php
/**
 * @package net.nehmer.static
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use midcom\workflow\dialog;
use midcom\datamanager\storage;

/**
 * n.n.static index page handler
 *
 * @package net.nehmer.static
 */
class net_nehmer_static_handler_view extends midcom_baseclasses_components_handler
{
    private array $_index_entries = [];

    private ?midcom_db_article $_article = null;

    private datamanager $_datamanager;

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
            $buttons[] = $workflow->get_button($this->router->generate('edit', ['guid' => $this->_article->guid]), [
                MIDCOM_TOOLBAR_ACCESSKEY => 'e',
            ]);
        }

        if (   $this->_article->topic === $this->_topic->id
            && $this->_article->can_do('midgard:delete')) {
            $delete = $this->get_workflow('delete', ['object' => $this->_article]);
            $buttons[] = $delete->get_button($this->router->generate('delete', ['guid' => $this->_article->guid]));
        }

        $this->_view_toolbar->add_items($buttons);
    }

    /**
     * Looks up an article to display. If the handler_id is 'index', the index article is tried to be
     * looked up, otherwise the article name is taken from args[0]. Triggered error messages are
     * generated accordingly. A missing index will trigger a forbidden error, a missing regular
     * article a 404.
     *
     * If create privileges apply, we relocate to the index creation article
     */
    public function _handler_view(string $handler_id, string $name, array &$data)
    {
        $qb = net_nehmer_static_viewer::get_topic_qb($this->_topic->id, $this->_config->get('sort_order'));
        $qb->add_constraint('name', '=', $name);
        $qb->add_constraint('up', '=', 0);
        $qb->set_limit(1);

        $this->_article = $qb->get_result(0);
        if (!$this->_article) {
            throw new midcom_error_notfound('Could not find ' . $name);
        }
        return $this->show_article($data, $handler_id == 'view_raw');
    }

    private function show_article(array &$data, bool $raw = false)
    {
        if ($raw) {
            midcom::get()->skip_page_style = true;
        }

        $this->_datamanager = new datamanager($data['schemadb']);
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

    public function _handler_index(array &$data)
    {
        if (!$this->_config->get('autoindex')) {
            $qb = net_nehmer_static_viewer::get_topic_qb($this->_topic->id, $this->_config->get('sort_order'));
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
                        $this->_request_data['schema'] = $schema->get_name();
                        dialog::add_head_elements();
                        return $this->show('index-missing');
                    }
                }

                throw new midcom_error_forbidden('Directory index forbidden');
            }

            return $this->show_article($data);
        }

        // Get last modified timestamp
        $qb = net_nehmer_static_viewer::get_topic_qb($this->_topic->id);

        $qb->add_order('metadata.revised', 'DESC');
        $qb->set_limit(1);
        $result = $qb->execute();
        $article_time = $result[0]->metadata->revised ?? 0;
        $topic_time = $this->_topic->metadata->revised;
        midcom::get()->metadata->set_request_metadata(max($article_time, $topic_time), null);

        $this->_index_entries = $this->_load_autoindex_data();
    }

    /**
     * Go over the topic and load all available objects for displaying in the autoindex.
     *
     * It will populate the request data key 'create_urls' as well. See the view handler for
     * further details.
     *
     * The computed array has the following keys:
     *
     * - string name: The name of the object.
     * - string url: The full URL to the object.
     * - string size: The formatted size of the document. This is only populated for attachments.
     * - string desc: The object title/description.
     * - string type: The MIME Type of the object.
     * - string lastmod: The localized last modified date.
     */
    private function _load_autoindex_data() : array
    {
        $view = [];
        $datamanager = new datamanager($this->_request_data['schemadb']);
        $qb = net_nehmer_static_viewer::get_topic_qb($this->_topic->id, $this->_config->get('sort_order'));
        $qb->add_order('title');
        $qb->add_order('name');

        foreach ($qb->execute() as $article) {
            try {
                $datamanager->set_storage($article);
            } catch (midcom_error $e) {
                $e->log();
                continue;
            }

            $this->_process_datamanager($datamanager, $article, $view);
        }

        return $view;
    }

    /**
     * Converts the main document to a view entry.
     */
    private function _process_datamanager(datamanager $datamanager, midcom_db_article $article, array &$view)
    {
        $formatter = $this->_l10n->get_formatter();
        $view_data = $datamanager->get_form()->getViewData();
        $filename = "{$article->name}/";

        $view[$filename]['article'] = $article;
        $view[$filename]['url'] = $this->router->generate('view', ['name' => $article->name]);
        $view[$filename]['formattedsize'] = midcom_helper_misc::filesize_to_string($article->metadata->size);
        $view[$filename]['description'] = $view_data->title;
        $view[$filename]['mimetype'] = 'text/html';
        $view[$filename]['lastmod'] = $formatter->datetime($article->metadata->revised);
        $view[$filename]['view_article'] = $datamanager->get_content_html();

        // Stop the press, if blobs should not be visible
        if (!$this->_config->get('show_blobs_in_autoindex')) {
            return;
        }

        foreach ($view_data as $field => $value) {
            if ($value instanceof storage\image) {
                $data = $datamanager->get_form()->get($field)->getViewData();
                if (!empty($data['main'])) {
                    $filename = "{$article->name}/{$data['main']['filename']}";
                    $data['main']['lastmod'] = $formatter->datetime($data['main']['lastmod']);
                    $view[$filename] = $data['main'];
                }
            } elseif ($value instanceof storage\blobs) {
                foreach ($datamanager->get_form()->get($field)->all() as $child) {
                    $data = $child->getViewData();
                    $data['lastmod'] = $formatter->datetime($data['lastmod']);
                    $filename = "{$article->name}/{$data['filename']}";
                    $view[$filename] = $data;
                }
            }
        }
    }

    /**
     * Displays the autoindex of the n.n.static. This is a list of all articles and attachments on
     * the current topic.
     */
    public function _show_index(string $handler_id, array &$data)
    {
        midcom_show_style('autoindex-start');

        if (!empty($this->_index_entries)) {
            foreach ($this->_index_entries as $filename => $thedata) {
                $data['filename'] = $filename;
                $data['data'] = $thedata;
                midcom_show_style('autoindex-item');
            }
        } else {
            midcom_show_style('autoindex-directory-empty');
        }

        midcom_show_style('autoindex-end');
    }
}
