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
implements midcom_services_permalinks_resolver
{
    /**
     * Prepare the indexer client
     */
    public function _on_reindex($topic, $config, &$indexer)
    {
        $qb_documents = org_openpsa_documents_document_dba::new_query_builder();
        $qb_documents->add_constraint('topic', '=', $topic->id);
        $qb_documents->add_constraint('nextVersion', '=', 0);
        $qb_documents->add_constraint('orgOpenpsaObtype', '=', org_openpsa_documents_document_dba::OBTYPE_DOCUMENT);
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

    public function resolve_object_link(midcom_db_topic $topic, midcom_core_dbaobject $object)
    {
        if ($object instanceof org_openpsa_documents_document_dba) {
            if ($object->topic == $topic->id) {
                return "document/{$object->guid}/";
            }
        }
        return null;
    }
}
