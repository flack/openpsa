<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use midcom\events\dbaevent;

/**
 * This class is the main access point into the MidCOM Indexer subsystem.
 *
 * It allows you to maintain and query the document index.
 *
 * Do not instantiate this class directly. Instead use the get_service
 * method on midcom_application using the service name 'indexer' to obtain
 * a running instance.
 *
 * @see midcom_services_indexer_document
 * @see midcom_services_indexer_backend
 * @see midcom_services_indexer_filter
 *
 * @todo Write code examples
 * @todo More elaborate class introduction.
 * @package midcom.services
 */
class midcom_services_indexer implements EventSubscriberInterface
{
    /**
     * @var midcom_services_indexer_backend
     */
    private $_backend;

    /**
     * @var boolean
     */
    private $_disabled;

    /**
     * Initialization
     */
    public function __construct(midcom_services_indexer_backend $backend = null)
    {
        $this->_backend = $backend;
        $this->_disabled = $this->_backend === null;
    }

    public static function getSubscribedEvents()
    {
        return [dbaevent::DELETE => ['handle_delete']];
    }

    public function handle_delete(dbaevent $event)
    {
        $this->delete($event->get_object()->guid);
    }

    /**
     * Simple helper, returns true if the indexer service is online, false if it is disabled.
     */
    public function enabled() : bool
    {
        return !$this->_disabled;
    }

    /**
     * Adds a document to the index.
     *
     * A finished document object must be passed to this object. If the index
     * already contains a record with the same Resource Identifier, the record
     * is replaced.
     *
     * Support of batch-indexing using an Array of documents instead of a single
     * document is possible (and strongly advised for performance reasons).
     *
     * @param mixed $documents One or more documents to be indexed, so this is either a
     *           midcom_services_indexer_document or an Array of these objects.
     * @return boolean Indicating success.
     */
    public function index($documents) : bool
    {
        if ($this->_disabled) {
            return true;
        }

        $batch = is_array($documents);
        if (!$batch) {
            $documents = [$documents];
        }
        if (empty($documents)) {
            // Nothing to do.
            return true;
        }

        foreach ($documents as $value) {
            $value->members_to_fields();
        }

        try {
            $this->_backend->index($documents);
            return true;
        } catch (Exception $e) {
            if ($batch) {
                throw $e;
            }
            debug_add("Indexing error: " . $e->getMessage(), MIDCOM_LOG_ERROR);
            return false;
        }
    }

    /**
     * Removes the document(s) with the given resource identifier(s) from the index.
     * Using GUIDs instead of RIs will delete all language versions
     *
     * @param array $RIs The resource identifier(s) of the document(s) that should be deleted.
     * @return boolean Indicating success.
     */
    public function delete($RIs) : bool
    {
        if ($this->_disabled) {
            return true;
        }
        $RIs = (array) $RIs;
        if (empty($RIs)) {
            // Nothing to do.
            return true;
        }
        try {
            $this->_backend->delete($RIs);
            return true;
        } catch (Exception $e) {
            debug_add("Deleting error: " . $e->getMessage(), MIDCOM_LOG_ERROR);
            return false;
        }
    }

    /**
     * Clear the index completely.
     *
     * This will drop the current index.
     */
    public function delete_all(string $constraint = '') : bool
    {
        if ($this->_disabled) {
            return true;
        }

        try {
            $this->_backend->delete_all($constraint);
            return true;
        } catch (Exception $e) {
            debug_add("Deleting error: " . $e->getMessage(), MIDCOM_LOG_ERROR);
            return false;
        }
    }

    /**
     * Query the index and, if set, restrict the query by a given filter.
     *
     * The filter argument is optional and may be a subclass of indexer_filter.
     * The backend determines what filters are supported and how they are
     * treated.
     *
     * The query syntax is also dependent on the backend. Refer to its documentation
     * how queries should be built.
     *
     * @param string $query The query, which must suit the backends query syntax. It is assumed to be in the site charset.
     * @param array $options Options that are passed straight to the backend
     * @return midcom_services_indexer_document[] An array of documents matching the query
     * @todo Refactor into multiple methods
     */
    public function query(string $query, midcom_services_indexer_filter $filter = null, array $options = []) : array
    {
        $result = [];
        if ($this->_disabled) {
            return $result;
        }

        // Do charset translations
        $query = midcom::get()->i18n->convert_to_utf8($query);

        try {
            $result_raw = $this->_backend->query($query, $filter, $options);
        } catch (Exception $e) {
            debug_add("Query error: " . $e->getMessage(), MIDCOM_LOG_ERROR);
            return $result;
        }

        foreach ($result_raw as $document) {
            $document->fields_to_members();
            /**
             * FIXME: Rethink program flow and especially take into account that not all documents are
             * created by midcom or even served by midgard
             */

            // midgard:read verification, we simply try to create an object instance
            // In the case, we distinguish between MidCOM documents, where we can check
            // the RI identified object directly, and pure documents, where we use the
            // topic instead.

            // Try to check topic only if the guid is actually set
            if (!empty($document->topic_guid)) {
                try {
                    midcom_db_topic::get_cached($document->topic_guid);
                } catch (midcom_error $e) {
                    // Skip document, the object is hidden.
                    debug_add("Skipping the generic document {$document->title}, its topic seems to be invisible, we cannot proceed.");
                    continue;
                }
            }

            // this checks acls!
            if ($document->is_a('midcom')) {
                // Try to retrieve object:
                // Strip language code from end of RI if it looks like "<GUID>_<LANG>"
                try {
                    midcom::get()->dbfactory->get_object_by_guid(preg_replace('/^([0-9a-f]{32,80})_[a-z]{2}$/', '\\1', $document->RI));
                } catch (midcom_error $e) {
                    // Skip document, the object is hidden, deleted or otherwise unavailable.
                    //@todo Maybe nonexistent objects should be removed from index?
                    continue;
                }
            }
            $result[] = $document;
        }
        return $result;
    }

    /**
     * Try to instantiate the most specific document class for the object given in the parameter.
     *
     * This factory method will work even if the indexer is disabled. You can check this
     * with the enabled() method of this class.
     *
     * @todo Move to a full factory pattern here to save document php file parsings where possible.
     *     This means that all document creations will in the future be handled by this method.
     */
    public function new_document(object $object) : midcom_services_indexer_document
    {
        // Scan for datamanager instances.
        if (is_a($object, 'midcom\datamanager\datamanager')) {
            return new midcom\datamanager\indexer\document($object);
        }
        if (is_a($object, 'midcom_helper_datamanager2_datamanager')) {
            return new midcom_helper_datamanager2_indexer_document($object);
        }

        if ($object instanceof midcom_core_dbaobject) {
            return new midcom_services_indexer_document_midcom($object);
        }
        throw new midcom_error('Unsupported object type');
    }
}
