<?php
/**
 * @package org.openpsa.documents
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Indexer client class
 *
 * @package org.openpsa.documents
 */
class org_openpsa_documents_midcom_indexer extends midcom_helper_datamanager2_indexer_client
{
    public function prepare_document(midcom_services_indexer_document &$document, midcom_helper_datamanager2_datamanager $dm)
    {
        if (is_a($dm->storage->object, 'midcom_db_topic')) {
            $document->title = $dm->storage->object->extra;
        }
    }
}
