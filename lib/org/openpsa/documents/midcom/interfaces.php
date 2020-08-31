<?php
/**
 * @package org.openpsa.documents
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\datamanager;

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
        $dm_documents = datamanager::from_schemadb($config->get('schemadb_document'));

        $qb_directories = org_openpsa_documents_directory::new_query_builder();
        $qb_directories->add_constraint('up', '=', $topic->id);
        $qb_directories->add_constraint('component', '=', $this->_component);
        $dm_directories = datamanager::from_schemadb($config->get('schemadb_directory'));

        $indexer = new org_openpsa_documents_midcom_indexer($topic, $indexer);
        $indexer->add_query('documents', $qb_documents, $dm_documents);
        $indexer->add_query('directories', $qb_directories, $dm_directories);

        return $indexer;
    }

    public function resolve_object_link(midcom_db_topic $topic, midcom_core_dbaobject $object) : ?string
    {
        if (   $object instanceof org_openpsa_documents_document_dba
            && $object->topic == $topic->id) {
            return "document/{$object->guid}/";
        }
        return null;
    }
}
