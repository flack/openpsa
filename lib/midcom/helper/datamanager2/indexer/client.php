<?php
/**
 * @package midcom.helper.datamanager2
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Indexer client base class
 *
 * @package midcom.helper.datamanager2
 */
abstract class midcom_helper_datamanager2_indexer_client extends midcom_services_indexer_client
{
    public function process_results($name, array $results, $schemadb)
    {
        $documents = array();
        $datamanager = new midcom_helper_datamanager2_datamanager($schemadb);

        foreach ($results as $object) {
            if (!$datamanager->autoset_storage($object)) {
                debug_add("Warning, failed to initialize datamanager for object {$object->id}. See debug log for details.", MIDCOM_LOG_WARN);
                debug_print_r('Object dump:', $object);
                continue;
            }

            $documents[] = $this->new_document($datamanager);
        }

        return $documents;
    }

    public function create_document($dm)
    {
        $document = new midcom_helper_datamanager2_indexer_document($dm);
        $document->read_metadata_from_object($dm->storage->object);
        $this->prepare_document($document, $dm);
        return $document;
    }

    abstract public function prepare_document(midcom_services_indexer_document &$document, midcom_helper_datamanager2_datamanager $dm);
}
