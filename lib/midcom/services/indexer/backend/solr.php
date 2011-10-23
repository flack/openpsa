<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @ignore
 */
require_once 'HTTP/Request2.php';

/**
 * Solr implementation of the indexer backend.
 *
 * This works by communicating with solr over http requests.
 *
 * @see midcom_services_indexer
 * @package midcom.services
 */
class midcom_services_indexer_backend_solr implements midcom_services_indexer_backend
{
    /**
     * The "index" to use (Solr has single index but we add this as query constraint as necessary
     */
    private $_index_name = null;

    /**
     * The xml factory class
     *
     * @var midcom_services_indexer_solrDocumentFactory
     */
    private $factory = null;

    /**
     * The http_request wrapper
     *
     * @var midcom_services_indexer_solrRequest
     */
    private $request = null;

    /**
     * Constructor is empty at this time.
     */
    public function __construct($index_name = null)
    {
        if (is_null($index_name))
        {
            $this->_index_name = $GLOBALS['midcom_config']['indexer_index_name'];
            if ($this->_index_name == 'auto')
            {
                $this->_index_name = midcom_connection::get_unique_host_name();
            }
        }
        else
        {
            $this->_index_name = $index_name;
        }
        $this->factory = new midcom_services_indexer_solrDocumentFactory($this->_index_name);
        $this->request = new midcom_services_indexer_solrRequest($this->factory);
    }

    /**
     * Adds a document to the index.
     *
     * Any warning will be treated as error.
     *
     * Note, that $document may also be an array of documents without further
     * changes to this backend.
     *
     * @param Array $documents A list of midcom_services_indexer_document objects.
     * @return boolean Indicating success.
     */
    public function index($documents)
    {
        $this->factory->reset();
        if (!is_array($documents))
        {
            $documents = array($documents);
        }

        $added = false;
        foreach ($documents as $document)
        {
            if (!$document->actually_index)
            {
                continue;
            }
            $this->factory->add($document);
            $added = true;
        }

        if (!$added)
        {
            return true;
        }

        return $this->request->execute();
    }

    /**
     * Removes the document(s) with the given resource identifier(s) from the index.
     *
     * @param array $RIs The resource identifier(s) of the document(s) that should be deleted.
     * @return boolean Indicating success.
     */
    public function delete($RIs)
    {
        $this->factory->reset();
        if (!is_array($RIs))
        {
            $RIs = array($RIs);
        }
        foreach ($RIs as $RI)
        {
            $this->factory->delete($RI);
        }
        return $this->request->execute();
    }

    /**
     * Clear the index completely.
     * This will drop the current index.
     * NB: It is probably better to just stop the indexer and delete the data/index directory!
     * @return boolean Indicating success.
     */
    function delete_all()
    {
        $this->factory->delete_all();
        return $this->request->execute(true);
    }

    /**
     * Query the index and, if set, restrict the query by a given filter.
     *
     * @param string $query The query, which must suite the backends query syntax.
     * @param midcom_services_indexer_filter $filter An optional filter used to restrict the query. This may be null indicating no filter.
     * @return Array An array of documents matching the query, or false on a failure.
     */
    public function query($query, $filter)
    {
        if ($filter !== null)
        {
            if ($filter->type == 'datefilter')
            {
                $format = "Y-m-dTH:i:s"  ; //1995-12-31T23:59:59Z
                $query .= sprintf(" AND %s:[%s TO %s]",
                                    $filter->get_field(),
                                    gmdate($format, $filter->get_start()) . "Z",
                                    gmdate($format, ($filter->get_end() == 0 ) ? time() : $filter->get_end()) . "Z");
            }
        }

        $url = "http://{$GLOBALS['midcom_config']['indexer_xmltcp_host']}:{$GLOBALS['midcom_config']['indexer_xmltcp_port']}/solr/select";

        $request = new HTTP_Request2($url, HTTP_Request2::METHOD_GET);
        $url = $request->getUrl();

        // FIXME: Make this configurable, even better: adapt the whole indexer system to fetching enable querying for counts and slices
        $maxrows = 1000;
        $url->setQueryVariables(array
        (
            'q' => $query,
            'fl' => '*,score',
            'rows' => $maxrows
        ));

        if (!empty($this->_index_name))
        {
            $url->setQueryVariable('fq', '__INDEX_NAME:"' . rawurlencode($this->_index_name) . '"');
        }

        $request->setHeader('Accept-Charset', 'UTF-8');
        $request->setHeader('Content-type', 'text/xml; charset=utf-8');

        try
        {
            $response = $request->send();
        }
        catch (Exception $e)
        {
            debug_add("Failed to execute request " . $url . ": " . $e->getMessage(), MIDCOM_LOG_WARN);
            return false;
        }
        $this->code = $response->getStatus();

        if ($this->code != 200)
        {
            debug_print_r($url . " returned response code {$this->code}, body:", $response->getBody());
            return false;
        }

        $body = $response->getBody();

        $response = DomDocument::loadXML($body);
        $xquery = new DomXPath($response);
        $result = array();

        $num = $xquery->query('/response/result')->item(0);
        if ($num->getAttribute('numFound') == 0)
        {
            return array();
        }

        foreach ($xquery->query('/response/result/doc') as $res)
        {
            $doc = new midcom_services_indexer_document();
            foreach ($res->childNodes as $str)
            {
                $name = $str->getAttribute('name');

                $doc->add_result($name, ($str->tagName == 'float') ? (float) $str->nodeValue : (string) $str->nodeValue);
                if ($name == 'RI') {
                    $doc->add_result('__RI', $str->nodeValue);
                }
                if ($name == 'score' && $filter == null) {
                    $doc->score = (float) $str->nodeValue;
                }
            }
            /* FIXME: before result slicing is properly supported this can be too heavy
            if (   isset($doc->source)
                && mgd_is_guid($doc->source))
            {
                $_MIDCOM->cache->content->register($doc->source);
            }
            */
            $result[] = $doc;
        }

        debug_add(sprintf('Returning %d results', count($result)), MIDCOM_LOG_INFO);
        return $result;
    }
}

/**
 * This class provides methods to make XML for the different solr xml requests.
 *
 * @package midcom.services
 * @see midcom_services_indexer
 */
class midcom_services_indexer_solrDocumentFactory
{
    /**
     * The "index" to use (Solr has single index but we add this as query constraint as necessary
     */
    private $_index_name = null;

    /**
     * The xml document to post.
     */
    var $document = null;

    public function __construct($index_name = null)
    {
        if (is_null($index_name))
        {
            $this->_index_name = $GLOBALS['midcom_config']['indexer_index_name'];
            if ($this->_index_name == 'auto')
            {
                $this->_index_name = midcom_connection::get_unique_host_name();
            }
        }
        else
        {
            $this->_index_name = $index_name;
        }
        $this->xml = new DomDocument('1.0', 'UTF-8');
    }

    function reset()
    {
        $this->xml = new DomDocument('1.0', 'UTF-8');
    }

    /**
     * Adds a document to the index.
     */
    public function add($document)
    {
        $root = $this->xml->createElement('add');
        $this->xml->appendChild($root);
        $element = $this->xml->createElement('doc');
        $this->xml->documentElement->appendChild($element);
        $field = $this->xml->createElement('field');
        $field->setAttribute('name', 'RI');
        $field->nodeValue = $document->RI;
        $element->appendChild($field);
        if (!empty($this->_index_name))
        {
            $field = $this->xml->createElement('field');
            $field->setAttribute('name', '__INDEX_NAME');
            $field->nodeValue = htmlspecialchars($this->_index_name);
            $element->appendChild($field);
        }

        foreach ($document->list_fields() as $field_name)
        {
            $field_record = $document->get_field_record($field_name);
            $field = $this->xml->createElement('field');
            $field->setAttribute('name', $field_record['name']);
            // Escape entities etc to prevent Solr from throwing a hissy fit
            $field->nodeValue = htmlspecialchars($field_record['content']);
            $element->appendChild($field);
        }
    }

    /**
     * Deletes one element
     *
     * @param string $id the element id
     */
    public function delete($id)
    {
        $root = $this->xml->createElement('delete');
        $this->xml->appendChild($root);
        $id_element = $this->xml->createElement('id');
        $this->xml->documentElement->appendChild($id_element);
        $id_element->nodeValue = $id;
    }

    /**
     * Deletes all elements with the id defined
     * (this should be all midgard documents)
     */
    public function delete_all()
    {
        $this->reset();
        $root = $this->xml->createElement('delete');
        $this->xml->appendChild($root);
        $element = $this->xml->createElement('delete');
        $this->xml->documentElement->appendChild($element);
        $query = $this->xml->createElement('delete');
        $element->appendChild($query);
        $query->nodeValue = "id:[ *TO* ]";
        if (!empty($this->_index_name))
        {
            $query->nodeValue .= ' AND __INDEX_NAME:"' . htmlspecialchars($this->_index_name) . '"';
        }
    }

    /**
     * Returns the generated XML
     */
    public function to_xml()
    {
        return $this->xml->saveXML();
    }
}


/**
 * This class handles the posting to the server.
 * It's a simple wrapper around the HTTP_request2 library.
 *
 * @package midcom.services
 */
class midcom_services_indexer_solrRequest
{
    /**
     * The HTTP_Request2 object
     *
     * @var HTTP_Request2
     */
    var $request = null;

    /**
     * The xml factory
     */
    var $factory = null;

    public function __construct ($factory, $index_name = null)
    {
        $this->factory = $factory;
    }

    function execute($optimize = false)
    {
        return $this->do_post($this->factory->to_xml(), $optimize);
    }

    /**
     * Posts the xml to the suggested url using HTTP_Request2.
     */
    function do_post($xml, $optimize = false)
    {
        $url = "http://" . $GLOBALS['midcom_config']['indexer_xmltcp_host'] .
            ":" . $GLOBALS['midcom_config']['indexer_xmltcp_port'] . "/solr/update";
        $this->request = new HTTP_Request2($url, HTTP_Request2::METHOD_POST);

        $this->request->setBody($xml);
        $this->request->setHeader('Accept-Charset', 'UTF-8');
        $this->request->setHeader('Content-type', 'text/xml; charset=utf-8');

        if (!$this->_send_request())
        {
            return false;
        }

        $this->request->setBody('<commit/>');

        if (!$this->_send_request())
        {
            return false;
        }

        if ($optimize)
        {
            $this->request->setBody('<optimize/>');
            if (!$this->_send_request())
            {
                return false;
            }
        }

        debug_add('POST ok');
        return true;
    }

    private function _send_request()
    {
        try
        {
            $response = $this->request->send();
        }
        catch (Exception $e)
        {
            debug_add("Failed to execute request " . $this->request->getUrl() . ": " . $e->getMessage(), MIDCOM_LOG_WARN);
            return false;
        }
        $this->code = $response->getStatus();

        if ($this->code != 200)
        {
            debug_print_r($this->request->getUrl() . " returned response code {$this->code}, body:", $response->getBody());
            debug_print_r('Request content:', $this->request->getBody());
            return false;
        }
        return true;
    }
}
?>