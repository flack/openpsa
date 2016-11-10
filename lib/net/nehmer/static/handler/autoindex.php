<?php
/**
 * @package net.nehmer.static
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midgard\introspection\helper;

/**
 * n.n.static Autoindex page handler
 *
 * @package net.nehmer.static
 */
class net_nehmer_static_handler_autoindex extends midcom_baseclasses_components_handler
{
    /**
     * The content topic to use
     *
     * @var midcom_db_topic
     */
    private $_content_topic;

    /**
     * The list of index entries
     *
     * @var array
     */
    protected $_index_entries = array();

    /**
     * @var midcom_services_i18n_formatter
     */
    private $formatter;

    /**
     * Maps the content topic from the request data to local member variables.
     */
    public function _on_initialize()
    {
        $this->_content_topic = $this->_request_data['content_topic'];
        $this->formatter = $this->_l10n->get_formatter();
    }

    /**
     * Shows the autoindex list. Nothing to do in the handle phase except setting last modified
     * dates.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_autoindex($handler_id, array $args, array &$data)
    {
        // Get last modified timestamp
        $qb = net_nehmer_static_viewer::get_topic_qb($this->_config, $this->_content_topic->id);

        $qb->add_order('metadata.revised', 'DESC');
        $qb->set_limit(1);
        $result = $qb->execute();
        $article_time = (!empty($result)) ? $result[0]->metadata->revised : 0;
        $topic_time = $this->_content_topic->metadata->revised;
        midcom::get()->metadata->set_request_metadata(max($article_time, $topic_time), null);

        $this->_index_entries = $this->_load_autoindex_data();
    }

    /**
     * Displays the autoindex of the n.n.static. This is a list of all articles and attachments on
     * the current topic.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_autoindex($handler_id, array &$data)
    {
        midcom_show_style('autoindex-start');

        if (count ($this->_index_entries) > 0) {
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
     *
     * @return Array Autoindex objects as outlined above
     */
    private function _load_autoindex_data()
    {
        $view = array();
        $datamanager = new midcom_helper_datamanager2_datamanager($this->_request_data['schemadb']);
        $qb = net_nehmer_static_viewer::get_topic_qb($this->_config, $this->_content_topic->id);

        $sort_order = 'ASC';
        $sort_property = $this->_config->get('sort_order');
        if (strpos($sort_property, 'reverse ') === 0) {
            $sort_order = 'DESC';
            $sort_property = substr($sort_property, strlen('reverse '));
        }
        if (strpos($sort_property, 'metadata.') === false) {
            $helper = new helper;
            $article = new midgard_article();
            if (!$helper->property_exists($article, $sort_property)) {
                $sort_property = 'metadata.' . $sort_property;
            }
        }
        $qb->add_order($sort_property, $sort_order);

        $qb->add_order('title');
        $qb->add_order('name');

        $result = $qb->execute();

        foreach ($result as $article) {
            if (!$datamanager->autoset_storage($article)) {
                debug_add("The datamanager for article {$article->id} could not be initialized, skipping it.");
                debug_print_r('Object was:', $article);
                continue;
            }

            $this->_process_datamanager($datamanager, $article, $view);
        }

        return $view;
    }

    /**
     * Converts the main document to a view entry.
     */
    private function _process_datamanager($datamanager, $article, array &$view)
    {
        $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
        $filename = "{$article->name}/";

        $view[$filename]['article'] = $article;
        $view[$filename]['name'] = $filename;
        $view[$filename]['url'] = $prefix . $filename;
        $view[$filename]['size'] = $article->metadata->size;
        $view[$filename]['desc'] = $datamanager->types['title']->value;
        $view[$filename]['type'] = 'text/html';
        $view[$filename]['lastmod'] = $this->formatter->datetime($article->metadata->revised);
        $view[$filename]['view_article'] = $datamanager->get_content_html();

        // Stop the press, if blobs should not be visible
        if (!$this->_config->get('show_blobs_in_autoindex')) {
            return;
        }

        foreach ($datamanager->schema->field_order as $name) {
            if ($datamanager->types[$name] instanceof midcom_helper_datamanager2_type_images) {
                foreach ($datamanager->types[$name]->images as $data) {
                    $filename = "{$article->name}/{$data['filename']}";
                    $view[$filename] = $this->_get_attachment_data($filename, $data);
                }
            } elseif (   $datamanager->types[$name] instanceof midcom_helper_datamanager2_type_image
                     && $datamanager->types[$name]->attachments_info) {
                $data = $datamanager->types[$name]->attachments_info['main'];
                $filename = "{$article->name}/{$data['filename']}";
                $view[$filename] = $this->_get_attachment_data($filename, $data);
            } elseif ($datamanager->types[$name] instanceof midcom_helper_datamanager2_type_blobs) {
                foreach ($datamanager->types[$name]->attachments_info as $data) {
                    $filename = "{$article->name}/{$data['filename']}";
                    $view[$filename] = $this->_get_attachment_data($filename, $data);
                }
            }
        }
    }

    private function _get_attachment_data($filename, array $data)
    {
        return array
        (
            'name' => $filename,
            'url' => $data['url'],
            'size' => $data['formattedsize'],
            'desc' => $data['filename'],
            'url' => $data['mimetype'],
            'lastmod' => $this->formatter->datetime($data['lastmod'])
        );
    }
}
