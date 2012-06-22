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
    public function __construct()
    {
        $this->_autoload_libraries = array
        (
            'org.openpsa.core',
        );
    }

    /**
     * Prepare the indexer client
     */
    public function _on_reindex($topic, $config, &$indexer)
    {
        $qb_documents = org_openpsa_documents_document_dba::new_query_builder();
        $qb_documents->add_constraint('topic', '=', $topic->id);
        $qb_documents->add_constraint('nextVersion', '=', 0);
        $qb_documents->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_DOCUMENT);
        $schemadb_documents = midcom_helper_datamanager2_schema::load_database($config->get('schemadb_document'));

        $qb_directories = org_openpsa_documents_directory::new_query_builder();
        $qb_directories->add_constraint('up', '=', $topic->id);
        $qb_directories->add_constraint('component', '=', $this->_component);
        $schemadb_directories = midcom_helper_datamanager2_schema::load_database($config->get('schemadb_directory'));

        $indexer = new org_openpsa_documents_midcom_indexer($topic, $indexer);
        $indexer->add_query('documents', $qb_documents, $schemadb_documents);
        $indexer->add_query('directories', $qb_directories, $schemadb_directories);

        return $indexer;
    }

    public function _on_resolve_permalink($topic, $config, $guid)
    {
        try
        {
            $document = new org_openpsa_documents_document_dba($guid);
            if ($document->topic != $topic->id)
            {
                return null;
            }
        }
        catch (midcom_error $e)
        {
            return null;
        }
        return "document/{$document->guid}/";
    }
}
?>