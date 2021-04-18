<?php
/**
 * @package org.openpsa.documents
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\datamanager;
use Symfony\Component\HttpFoundation\Request;

/**
 * org.openpsa.documents search handler and viewer class.
 *
 * @package org.openpsa.documents
 */
class org_openpsa_documents_handler_search extends midcom_baseclasses_components_handler
{
    /**
     * @var datamanager
     */
    private $datamanager;

    /**
     * @var midcom_services_indexer_document[]
     */
    private $results;

    public function _on_initialize()
    {
        $this->datamanager = datamanager::from_schemadb($this->_config->get('schemadb_document'));
    }

    public function _handler_search(Request $request, array &$data)
    {
        if ($request->query->has('query')) {
            // Figure out where we are
            $nap = new midcom_helper_nav();
            $node = $nap->get_node($nap->get_current_node());

            // Instantiate indexer
            $indexer = midcom::get()->indexer;

            // Add the search parameters
            $query = $request->query->get('query');

            $filter = new midcom_services_indexer_filter_chained;
            $filter->add_filter(new midcom_services_indexer_filter_string('__TOPIC_URL', '"' . $node[MIDCOM_NAV_FULLURL] . '*"'));
            $filter->add_filter(new midcom_services_indexer_filter_string('__COMPONENT', '"' . $this->_component . '"'));
            // TODO: Metadata support

            // Run the search
            $this->results = $indexer->query($query, $filter);
        }

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.documents/layout.css");

        $this->_populate_toolbar();
    }

    /**
     * Add toolbar items
     */
    private function _populate_toolbar()
    {
        if ($this->_request_data['directory']->can_do('midgard:create')) {
            $workflow = $this->get_workflow('datamanager');
            $this->_view_toolbar->add_item($workflow->get_button($this->router->generate('document-create'), [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('new document'),
                MIDCOM_TOOLBAR_GLYPHICON => 'file-o',
            ]));
            $this->_view_toolbar->add_item($workflow->get_button($this->router->generate('directory-create'), [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('new directory'),
                MIDCOM_TOOLBAR_GLYPHICON => 'folder-o',
            ]));
        }
    }

    /**
     * @param array $data The local request data.
     */
    public function _show_search(string $handler_id, array &$data)
    {
        $displayed = 0;
        midcom_show_style('show-search-header');
        if (!empty($this->results)) {
            midcom_show_style('show-search-results-header');
            foreach ($this->results as $document) {
                try {
                    // $obj->RI will contain either document or attachment GUID depending on match,
                    // ->source will always contain the document GUID
                    $data['document'] = new org_openpsa_documents_document_dba($document->source);
                    $this->datamanager->set_storage($data['document']);
                } catch (midcom_error $e) {
                    $e->log();
                    continue;
                }
                $data['document_dm'] = $this->datamanager->get_content_raw();
                $data['document_attachment'] = array_shift($data['document_dm']['document']);
                $data['document_search'] = $document;
                midcom_show_style('show-search-results-item');
                $displayed++;
            }
            midcom_show_style('show-search-results-footer');
        }
        if ($displayed == 0) {
            midcom_show_style('show-search-noresults');
        }
        midcom_show_style('show-search-footer');
    }
}
