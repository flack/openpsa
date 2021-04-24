<?php
/**
 * @package net.nehmer.static
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\storage;

/**
 * n.n.static Autoindex page handler
 *
 * @package net.nehmer.static
 */
class net_nehmer_static_handler_autoindex extends midcom_baseclasses_components_handler
{
    /**
     * The list of index entries
     *
     * @var array
     */
    protected $_index_entries = [];

    /**
     * @var midcom_services_i18n_formatter
     */
    private $formatter;

    /**
     * Maps the content topic from the request data to local member variables.
     */
    public function _on_initialize()
    {
        $this->formatter = $this->_l10n->get_formatter();
    }

    /**
     * Shows the autoindex list. Nothing to do in the handle phase except setting last modified
     * dates.
     */
    public function _handler_autoindex()
    {
        // Get last modified timestamp
        $qb = net_nehmer_static_viewer::get_topic_qb($this->_topic->id);

        $qb->add_order('metadata.revised', 'DESC');
        $qb->set_limit(1);
        $result = $qb->execute();
        $article_time = (!empty($result)) ? $result[0]->metadata->revised : 0;
        $topic_time = $this->_topic->metadata->revised;
        midcom::get()->metadata->set_request_metadata(max($article_time, $topic_time), null);

        $this->_index_entries = $this->_load_autoindex_data();
    }

    /**
     * Displays the autoindex of the n.n.static. This is a list of all articles and attachments on
     * the current topic.
     */
    public function _show_autoindex(string $handler_id, array &$data)
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
        $view_data = $datamanager->get_form()->getViewData();
        $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
        $filename = "{$article->name}/";

        $view[$filename]['article'] = $article;
        $view[$filename]['url'] = $prefix . $filename;
        $view[$filename]['formattedsize'] = midcom_helper_misc::filesize_to_string($article->metadata->size);
        $view[$filename]['description'] = $view_data->title;
        $view[$filename]['mimetype'] = 'text/html';
        $view[$filename]['lastmod'] = $this->formatter->datetime($article->metadata->revised);
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
                    $data['main']['lastmod'] = $this->formatter->datetime($data['main']['lastmod']);
                    $view[$filename] = $data['main'];
                }
            } elseif ($value instanceof storage\blobs) {
                foreach ($datamanager->get_form()->get($field)->all() as $child) {
                    $data = $child->getViewData();
                    $data['lastmod'] = $this->formatter->datetime($data['lastmod']);
                    $filename = "{$article->name}/{$data['filename']}";
                    $view[$filename] = $data;
                }
            }
        }
    }
}
