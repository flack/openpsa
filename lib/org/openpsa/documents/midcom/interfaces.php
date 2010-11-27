<?php
/**
 * @package org.openpsa.documents
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * OpenPSA document management system
 *
 * @package org.openpsa.documents
 */
class org_openpsa_documents_interface extends midcom_baseclasses_components_interface
{
    function __construct()
    {
        $this->_autoload_libraries = array
        (
            'org.openpsa.core',
        );
    }

    /**
     * Iterate over all documents and create index record using the datamanager indexer
     * method.
     */
    function _on_reindex($topic, $config, &$indexer)
    {
        $_MIDCOM->load_library('midcom.helper.datamanager2');

        $qb = org_openpsa_documents_document_dba::new_query_builder();
        $qb->add_constraint('topic', '=', $topic->id);
        $qb->add_constraint('nextVersion', '=', 0);
        $qb->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_DOCUMENT);
        $ret = $qb->execute();
        if (   is_array($ret)
            && count($ret) > 0)
        {
            $schema = midcom_helper_datamanager2_schema::load_database($config->get('schemadb_document'));
            $datamanager = new midcom_helper_datamanager2_datamanager($schema);
            if (!$datamanager)
            {
                debug_add('Warning, failed to create a datamanager instance with this schemapath:' . $this->_config->get('schemadb_document'),
                    MIDCOM_LOG_WARN);
                return false;
            }

            foreach ($ret as $document)
            {
                if (!$datamanager->autoset_storage($document))
                {
                    debug_add("Warning, failed to initialize datamanager for document {$document->id}. See Debug Log for details.", MIDCOM_LOG_WARN);
                    debug_print_r('Document dump:', $document);

                    continue;
                }

                $indexer->index($datamanager);
            }
        }
        return true;
    }

    function _on_resolve_permalink($topic, $config, $guid)
    {
        $document = new org_openpsa_documents_document_dba($guid);
        if (   ! $document
            || $document->topic != $topic->id)
        {
            return null;
        }
        return "document/{$document->guid}/";
    }
}
?>