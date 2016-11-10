<?php
/**
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace midcom\datamanager\indexer;
use midcom\datamanager\datamanager;
use midcom_services_indexer_client;
use midcom_services_indexer_document;

/**
 * Indexer client base class
 */
abstract class client extends midcom_services_indexer_client
{
    public function process_results($name, array $results, $datamanager)
    {
        $documents = array();

        foreach ($results as $object) {
            if (!$datamanager->set_storage($object)) {
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
        $document = new document($dm);
        $document->read_metadata_from_object($dm->get_storage()->get_value());
        $this->prepare_document($document, $dm);
        return $document;
    }

    abstract public function prepare_document(midcom_services_indexer_document &$document, datamanager $dm);
}
