<?php
/**
 * @package org.openpsa.documents
 * @author Nemein Oy http://www.nemein.com/
 * @version $Id: search.php 26715 2010-10-23 10:14:57Z flack $
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.documents search handler and viewer class.
 *
 * @package org.openpsa.documents
 *
 */
class org_openpsa_documents_handler_search extends midcom_baseclasses_components_handler
{
    var $_datamanagers;

    function _on_initialize()
    {
        $_MIDCOM->load_library('midcom.helper.datamanager2');
        $schema = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_document'));
        $this->_datamanagers['document'] = new midcom_helper_datamanager2_datamanager($schema);
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_search($handler_id, $args, &$data)
    {
        $this->_request_data['results'] = array();
        if (array_key_exists('query', $_GET))
        {
            // Figure out where we are
            $nap = new midcom_helper_nav();
            $node = $nap->get_node($nap->get_current_node());

            // Instantiate indexer
            $indexer = $_MIDCOM->get_service('indexer');

            // Add the search parameters
            $query = $_GET['query'];
            $query .= " AND __TOPIC_URL:\"{$node[MIDCOM_NAV_FULLURL]}*\"";
            $query .= " AND __COMPONENT:org.openpsa.documents";
            // TODO: Metadata support

            // Run the search
            $this->_request_data['results'] = $indexer->query($query, null);
        }

        $_MIDCOM->add_link_head
        (
            array
            (
                'rel'   => 'stylesheet',
                'type'  => 'text/css',
                'media' => 'all',
                'href'  => MIDCOM_STATIC_URL . "/org.openpsa.documents/layout.css",
            )
        );

        $this->_populate_toolbar();

        return true;
    }

    /**
     * Helper that adds toolbar items
     */
    private function _populate_toolbar()
    {
        if ($_MIDCOM->auth->can_do('midgard:create', $this->_request_data['directory']))
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => 'document/create/',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('new document'),
                    MIDCOM_TOOLBAR_HELPTEXT => '',
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-text.png',
                    MIDCOM_TOOLBAR_ENABLED => true,
                )
            );
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => 'create/',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('new directory'),
                    MIDCOM_TOOLBAR_HELPTEXT => '',
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-dir.png',
                    MIDCOM_TOOLBAR_ENABLED => true,
                )
            );
        }
    }

    private function _load_document($guid)
    {
        $document = new org_openpsa_documents_document_dba($guid);
        if (!is_object($document))
        {
            return false;
        }
        
        return $document;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_search($handler_id, &$data)
    {
        $displayed = 0;
        midcom_show_style('show-search-header');
        if (!empty($this->_request_data['results']))
        {
            midcom_show_style('show-search-results-header');
            foreach ($this->_request_data['results'] as $document)
            {
                // $obj->RI will contain either document or attachment GUID depending on match, ->source will always contain the document GUID
                $this->_request_data['document'] = $this->_load_document($document->source);
                if ($this->_request_data['document'])
                {
                    $this->_datamanagers['document']->autoset_storage($this->_request_data['document']);
                    $this->_request_data['document_dm'] = $this->_datamanagers['document']->get_content_raw();
                    $this->_request_data['document_attachment'] = array_shift($this->_datamanagers['document']->types['document']->attachments_info);
                    $this->_request_data['document_search'] = $document;
                    midcom_show_style('show-search-results-item');
                    $displayed++;
                }
            }
            midcom_show_style('show-search-results-footer');
        }
        if ($displayed == 0)
        {
            midcom_show_style('show-search-noresults');
        }
        midcom_show_style('show-search-footer');
    }
}
?>