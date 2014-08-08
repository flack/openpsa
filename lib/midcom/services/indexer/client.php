<?php
/**
 * @package midcom.services
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Indexer client base class
 *
 * @package midcom.services
 */
abstract class midcom_services_indexer_client
{
    /**
     * The topic we're working on
     *
     * @var midcom_db_topic
     */
    protected $_topic;

    /**
     * The NAP node corresponding to the topic
     *
     * @var array
     */
    protected $_node;

    /**
     * The L10n DB for the topic's component
     *
     * @var midcom_services_i18n_l10n
     */
    protected $_l10n;

    /**
     * The indexer service
     *
     * @var midcom_services_indexer
     */
    private $_indexer;

    /**
     * The queries we will work on. Each entry consists of a querybuilder
     * instance and a datamanager to render the results, and is indexed by name
     *
     * @var array
     */
    private $_queries = array();

    /**
     * Constructor
     *
     * @param midcom_db_topic $topic The current topic
     * @param midcom_service_indexer $indexer The indexer service
     */
    public function __construct($topic, midcom_services_indexer $indexer = null)
    {
        $this->_topic = $topic;
        $this->_l10n = midcom::get()->i18n->get_l10n($topic->component);
        if (null === $indexer)
        {
            $indexer = midcom::get()->indexer;
        }
        $this->_indexer = $indexer;

        $nav = new midcom_helper_nav();
        $this->_node = $nav->get_node($this->_topic->id);
    }

    /**
     * Index a single object from DM2
     *
     * @param midcom_helper_datamanager2_datamanager $dm The datamanager2 instance to use
     */
    public function index(midcom_helper_datamanager2_datamanager $dm)
    {
        return $this->_indexer->index($this->_create_document($dm));
    }

    public function add_query($name, midcom_core_querybuilder $qb, array $schemadb)
    {
        $this->_queries[$name] = array($qb, $schemadb);
    }

    public function reindex()
    {
        foreach ($this->_queries as $name => $data)
        {
            $documents = $this->_process_query($name, $data[0], $data[1]);
            if (!empty($documents))
            {
                $this->_indexer->index($documents);
            }
        }
    }

    private function _process_query($name, $qb, $schemadb)
    {
        $results = $qb->execute();
        if (empty($results))
        {
            return array();
        }
        $documents = array();
        $datamanager = new midcom_helper_datamanager2_datamanager($schemadb);

        foreach ($results as $object)
        {
            if (!$datamanager->autoset_storage($object))
            {
                debug_add("Warning, failed to initialize datamanager for object {$object->id}. See debug log for details.", MIDCOM_LOG_WARN);
                debug_print_r('Object dump:', $object);
                continue;
            }

            $documents[] = $this->_create_document($datamanager);
        }

        return $documents;
    }

    private function _create_document(midcom_helper_datamanager2_datamanager $dm)
    {
        $document = $this->_indexer->new_document($dm);
        $document->topic_guid = $this->_topic->guid;
        $document->component = $this->_topic->component;
        $document->topic_url = $this->_node[MIDCOM_NAV_FULLURL];
        $document->read_metadata_from_object($dm->storage->object);
        $this->prepare_document($document, $dm);
        return $document;
    }

    abstract public function prepare_document(midcom_services_indexer_document &$document, midcom_helper_datamanager2_datamanager $dm);
}
