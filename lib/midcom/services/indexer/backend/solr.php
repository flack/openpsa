<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Buzz\Browser;
use Buzz\Message\Request;
use Buzz\Message\RequestInterface;

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
        if (is_null($index_name)) {
            $this->_index_name = midcom::get()->config->get('indexer_index_name');
            if ($this->_index_name == 'auto') {
                $this->_index_name = midcom_connection::get_unique_host_name();
            }
        } else {
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
     * @param array $documents A list of midcom_services_indexer_document objects.
     * @return boolean Indicating success.
     */
    public function index(array $documents)
    {
        $this->factory->reset();

        $added = false;
        foreach ($documents as $document) {
            if (!$document->actually_index) {
                continue;
            }
            $this->factory->add($document);
            $added = true;
        }

        if (!$added) {
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
    public function delete(array $RIs)
    {
        $this->factory->reset();
        array_map(array($this->factory, 'delete'), $RIs);
        return $this->request->execute();
    }

    /**
     * Clear the index completely or by constraint.
     *
     * @return boolean Indicating success.
     */
    function delete_all($constraint)
    {
        $this->factory->delete_all($constraint);
        return $this->request->execute(empty($constraint));
    }

    /**
     * {@inheritDoc}
     */
    public function query($querystring, midcom_services_indexer_filter $filter = null, array $options = array())
    {
        $url = 'http://' . midcom::get()->config->get('indexer_xmltcp_host') . ':' . midcom::get()->config->get('indexer_xmltcp_port') . '/solr/select';

        // FIXME: adapt the whole indexer system to fetching enable querying for counts and slices
        $query = array_merge(midcom::get()->config->get('indexer_config_options'), $options);
        $query['q'] = $querystring;

        if (!empty($this->_index_name)) {
            $query['fq'] = '__INDEX_NAME:"' . rawurlencode($this->_index_name) . '"';
        }
        if ($filter !== null) {
            $query['fq'] = (isset($query['fq']) ? $query['fq'] . ' AND ' : '') . $filter->get_query_string();
        }

        $url = $url . '?' . http_build_query($query);

        $headers = array
        (
            'Accept-Charset' => 'UTF-8',
            'Content-type' => 'text/xml; charset=utf-8',
            'Connection' => 'close'
        );

        $browser = new Browser;

        try {
            $response = $browser->get($url, $headers);
        } catch (Exception $e) {
            debug_add("Failed to execute request " . $url . ": " . $e->getMessage(), MIDCOM_LOG_WARN);
            return false;
        }
        $this->code = $response->getStatusCode();

        if ($this->code != 200) {
            debug_print_r($url . " returned response code {$this->code}, body:", $response->getContent());
            return false;
        }

        $document = new DomDocument;
        $document->loadXML($response->getContent());
        $xquery = new DomXPath($document);
        $result = array();

        $num = $xquery->query('/response/result')->item(0);
        if ($num->getAttribute('numFound') == 0) {
            return $result;
        }

        foreach ($xquery->query('/response/result/doc') as $res) {
            $doc = new midcom_services_indexer_document();
            foreach ($res->childNodes as $str) {
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
                midcom::get()->cache->content->register($doc->source);
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
     *
     * @var DomDocument
     */
    private $xml;

    public function __construct($index_name)
    {
        $this->_index_name = $index_name;
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
        if (empty($this->xml->documentElement)) {
            $root = $this->xml->createElement('add');
            $this->xml->appendChild($root);
        }
        $element = $this->xml->createElement('doc');
        $this->xml->documentElement->appendChild($element);
        $field = $this->xml->createElement('field');
        $field->setAttribute('name', 'RI');
        $field->nodeValue = $document->RI;
        $element->appendChild($field);
        if (!empty($this->_index_name)) {
            $field = $this->xml->createElement('field');
            $field->setAttribute('name', '__INDEX_NAME');
            $field->nodeValue = htmlspecialchars($this->_index_name);
            $element->appendChild($field);
        }

        foreach ($document->list_fields() as $field_name) {
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
        $query = $this->xml->createElement('query');
        $this->xml->documentElement->appendChild($query);
        $query->nodeValue = 'RI:' . $id . '*';
        if (!empty($this->_index_name)) {
            $query->nodeValue .= ' AND __INDEX_NAME:"' . htmlspecialchars($this->_index_name) . '"';
        }
    }

    /**
     * Deletes all elements with the id defined
     * (this should be all midgard documents)
     */
    public function delete_all($constraint)
    {
        $this->reset();
        $root = $this->xml->createElement('delete');
        $this->xml->appendChild($root);
        $query = $this->xml->createElement('query');
        $this->xml->documentElement->appendChild($query);
        $query->nodeValue = "RI:[ * TO * ]";
        if (!empty($constraint)) {
            $query->nodeValue .= ' AND ' . $constraint;
        }
        if (!empty($this->_index_name)) {
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
 * It's a simple wrapper around the Buzz library.
 *
 * @package midcom.services
 */
class midcom_services_indexer_solrRequest
{
    /**
     * The Buzz Request object
     *
     * @var Buzz\Message\Request
     */
    private $request;

    /**
     * The xml factory
     */
    private $factory;

    public function __construct($factory)
    {
        $this->factory = $factory;
    }

    function execute($optimize = false)
    {
        return $this->do_post($this->factory->to_xml(), $optimize);
    }

    /**
     * Posts the xml to the suggested url using Buzz.
     */
    function do_post($xml, $optimize = false)
    {
        $host = "http://" . midcom::get()->config->get('indexer_xmltcp_host') .
            ":" . midcom::get()->config->get('indexer_xmltcp_port');
        $this->request = new Request(RequestInterface::METHOD_POST, "/solr/update", $host);

        $this->request->setContent($xml);
        $this->request->addHeader('Accept-Charset: UTF-8');
        $this->request->addHeader('Content-type: text/xml; charset=utf-8');
        $this->request->addHeader('Connection: close');

        if (!$this->_send_request()) {
            return false;
        }

        if ($optimize) {
            $this->request->setContent('<optimize/>');
        } else {
            $this->request->setContent('<commit/>');
        }

        if (!$this->_send_request()) {
            return false;
        }

        if ($optimize) {
            $this->request->setContent('<optimize/>');
            if (!$this->_send_request()) {
                return false;
            }
        }

        debug_add('POST ok');
        return true;
    }

    private function _send_request()
    {
        $browser = new Browser;
        try {
            $response = $browser->send($this->request);
        } catch (Exception $e) {
            debug_add("Failed to execute request " . $this->request->getUrl() . ": " . $e->getMessage(), MIDCOM_LOG_WARN);
            return false;
        }
        $this->code = $response->getStatusCode();

        if ($this->code != 200) {
            debug_print_r($this->request->getUrl() . " returned response code {$this->code}, body:", $response->getContent());
            debug_print_r('Request content:', $this->request->getContent());
            return false;
        }
        return true;
    }
}
