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
 * @todo Batch indexing support
 * @todo Write code examples
 * @todo More elaborate class introduction.
 * @package midcom.services
 */
class midcom_services_indexer implements EventSubscriberInterface
{
    /**
     * The backend indexer implementation
     *
     * @var midcom_services_indexer_backend
     */
    private $_backend;

    /**
     * Flag for disabled indexing, set by the constructor.
     *
     * @var boolean
     */
    private $_disabled = false;

    /**
     * Initialization
     *
     * The constructor will initialize the indexer backend using the MidCOM
     * configuration by default. If you need a different indexer backend, you
     * can always explicitly instantiate a backend and pass it to the
     * constructor. In that case you have to load the corresponding PHP file
     * manually.
     *
     * @param midcom_services_indexer_backend $backend An explicit indexer to initialize with.
     */
    public function __construct($backend = null)
    {
        if (!midcom::get()->config->get('indexer_backend')) {
            $this->_disabled = true;
            return;
        }

        if ($backend === null) {
            $class = midcom::get()->config->get('indexer_backend');
            if (strpos($class, '_') === false) {
                // Built-in backend called using the shorthand notation
                $class = "midcom_services_indexer_backend_" . $class;
            }

            $this->_backend = new $class();
        } else {
            $this->_backend = $backend;
        }
        midcom::get()->dispatcher->addSubscriber($this);
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
     *
     * @return boolean Service state.
     */
    public function enabled()
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
    public function index($documents)
    {
        if ($this->_disabled) {
            return true;
        }

        if (!is_array($documents)) {
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
    public function delete($RIs)
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
     *
     * @return boolean Indicating success.
     */
    public function delete_all($constraint = '')
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
     * @param midcom_services_indexer_filter $filter An optional filter used to restrict the query.
     * @param array $options Options that are passed straight to the backend
     * @return midcom_services_indexer_document[] An array of documents matching the query, or false on a failure.
     * @todo Refactor into multiple methods
     */
    public function query($query, midcom_services_indexer_filter $filter = null, array $options = [])
    {
        if ($this->_disabled) {
            return false;
        }

        // Do charset translations
        $i18n = midcom::get()->i18n;
        $query = $i18n->convert_to_utf8($query);

        try {
            $result_raw = $this->_backend->query($query, $filter, $options);
        } catch (Exception $e) {
            debug_add("Query error: " . $e->getMessage(), MIDCOM_LOG_ERROR);
            return false;
        }

        $result = [];
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
     * This class will not return empty document base class instances if nothing
     * specific can be found. If you are in this situation, you need to instantiate
     * an appropriate document manually and populate it.
     *
     * The checking sequence is like this right now:
     *
     * 1. If a datamanager instance is passed, it is transformed into a datamanager document.
     * 2. If a Metadata object is passed, it is transformed into a midcom_services_indexer_document_midcom.
     * 3. Next, the method tries to retrieve a MidCOM Metadata object using the parameter directly. If successful,
     *    again, a midcom_services_indexer_document_midcom is returned.
     *
     * This factory method will work even if the indexer is disabled. You can check this
     * with the enabled() method of this class.
     *
     * @todo Move to a full factory pattern here to save document php file parsings where possible.
     *     This means that all document creations will in the future be handled by this method.
     *
     * @param object $object The object for which a document instance is required
     * @return midcom_services_indexer_document A valid document class as specific as possible. Returns
     *     false on error or if no specific class match could be found.
     */
    function new_document($object)
    {
        // Scan for datamanager instances.
        if (is_a($object, 'midcom\datamanager\datamanager')) {
            debug_add('This is a datamanager document');
            return new midcom\datamanager\indexer\document($object);
        }
        if (is_a($object, 'midcom_helper_datamanager2_datamanager')) {
            debug_add('This is a datamanager2 document');
            return new midcom_helper_datamanager2_indexer_document($object);
        }

        // Maybe we have a metadata object...
        if (is_a($object, midcom_helper_metadata::class)) {
            debug_add('This is a metadata document, built from a metadata object.');
            return new midcom_services_indexer_document_midcom($object);
        }

        // Try to get a metadata object for the argument passed
        // This should catch all DBA objects as well.
        if ($metadata = midcom_helper_metadata::retrieve($object)) {
            debug_add('Successfully fetched a Metadata object for the argument.');
            return new midcom_services_indexer_document_midcom($metadata);
        }

        // No specific match found.
        debug_print_r('No match found for this type:', $object);
        return false;
    }
}
