<?php
/**
 * @package midcom.services
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

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
     * @var midcom_services_indexer_solrDocumentFactory
     */
    private $factory;

    /**
     * @var midcom_config
     */
    private $config;

    public function __construct(midcom_config $config)
    {
        $this->config = $config;
        $this->factory = new midcom_services_indexer_solrDocumentFactory;
    }

    /**
     * Adds a document to the index.
     *
     * Any warning will be treated as error.
     *
     * @param midcom_services_indexer_document[] $documents A list of objects.
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

        if ($added) {
            $this->post();
        }
    }

    /**
     * Removes the document(s) with the given resource identifier(s) from the index.
     *
     * @param array $RIs The resource identifier(s) of the document(s) that should be deleted.
     */
    public function delete(array $RIs)
    {
        $this->factory->reset();
        array_map([$this->factory, 'delete'], $RIs);
        $this->post();
    }

    /**
     * Clear the index completely or by constraint.
     */
    public function delete_all(string $constraint)
    {
        $this->factory->delete_all($constraint);
        $this->post(empty($constraint));
    }

    /**
     * Posts the xml to the suggested url using Buzz.
     */
    private function post(bool $optimize = false)
    {
        $request = $this->prepare_request('update', $this->factory->to_xml(), 'POST');
        $this->send_request($request);

        $request = $this->prepare_request('update', ($optimize) ? '<optimize/>' : '<commit/>', 'POST');
        $this->send_request($request);
    }

    /**
     * {@inheritDoc}
     */
    public function query(string $querystring, midcom_services_indexer_filter $filter = null, array $options = []) : array
    {
        // FIXME: adapt the whole indexer system to fetching enable querying for counts and slices
        $query = array_merge($this->config->get_array('indexer_config_options'), $options);
        $query['q'] = $querystring;

        if ($filter !== null) {
            $query['fq'] = (isset($query['fq']) ? $query['fq'] . ' AND ' : '') . $filter->get_query_string();
        }
        $query['wt'] = 'xml';

        $request = $this->prepare_request('select?' . http_build_query($query));
        $response = $this->send_request($request);

        $document = new DOMDocument;
        $document->preserveWhiteSpace = false;
        $document->loadXML((string) $response->getBody());
        $xquery = new DOMXPath($document);
        $result = [];

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
                if ($name == 'score') {
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

    private function prepare_request(string $action, string $body = null, string $method = 'GET') : Request
    {
        $uri = "http://" . $this->config->get('indexer_xmltcp_host');
        $uri .= ":" . $this->config->get('indexer_xmltcp_port');

        $uri .= '/solr/';
        if ($this->config->get('indexer_xmltcp_core')) {
            $uri .= $this->config->get('indexer_xmltcp_core') . '/';
        }
        $uri .= $action;

        return new Request($method, $uri, [
            'Accept-Charset' => 'UTF-8',
            'Content-Type' => 'application/' . ($method == 'GET' ? 'x-www-form-urlencoded' : 'xml') . '; charset=utf-8',
            'Connection' => 'close'
        ], $body);
    }

    private function send_request(Request $request) : ResponseInterface
    {
        $client = new Client();
        $response = $client->send($request);

        $code = $response->getStatusCode();
        if ($code != 200) {
            debug_print_r('Request content:', (string) $request->getBody());
            throw new midcom_error((string) $response->getReasonPhrase(), $code);
        }
        return $response;
    }
}