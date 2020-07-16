<?php
/**
 * @package midcom.helper.search
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use Symfony\Component\HttpFoundation\Request;

/**
 * Search handler
 *
 * @package midcom.helper.search
 */
class midcom_helper_search_handler_search extends midcom_baseclasses_components_handler
{
    /**
     * Search form handler, nothing to do here.
     *
     * It uses the handler ID to distinguish between basic and advanced search forms.
     */
    public function _handler_searchform(Request $request, string $handler_id)
    {
        $this->prepare_formdata($handler_id);
        $this->populate_toolbar($request);
        return $this->show('search_form');
    }

    private function prepare_formdata($handler_id)
    {
        $this->_request_data['query'] = (array_key_exists('query', $_REQUEST) ? $_REQUEST['query'] : '');
        if ($handler_id === 'advanced') {
            $this->_request_data['request_topic'] = (array_key_exists('topic', $_REQUEST) ? $_REQUEST['topic'] : '');
            $this->_request_data['component'] = (array_key_exists('component', $_REQUEST) ? $_REQUEST['component'] : '');
            $this->_request_data['lastmodified'] = (array_key_exists('lastmodified', $_REQUEST) ? ((integer) $_REQUEST['lastmodified']) : 0);

            $this->_request_data['topics'] = ['' => $this->_l10n->get('search anywhere')];
            $this->_request_data['components'] = ['' => $this->_l10n->get('search all content types')];

            $nap = new midcom_helper_nav();
            $this->search_nodes($nap->get_root_node(), $nap, '');
        }
        $this->_request_data['type'] = $handler_id;
    }

    /**
     * Prepare the topic and component listings, this is a bit work intensive though,
     * we need to traverse everything.
     */
    private function search_nodes($node_id, midcom_helper_nav $nap, $prefix)
    {
        $node = $nap->get_node($node_id);

        if (   !array_key_exists($node[MIDCOM_NAV_COMPONENT], $this->_request_data['components'])
            && $node[MIDCOM_NAV_COMPONENT] != 'midcom.helper.search') {
            $l10n = $this->_i18n->get_l10n($node[MIDCOM_NAV_COMPONENT]);
            $this->_request_data['components'][$node[MIDCOM_NAV_COMPONENT]] = $l10n->get($node[MIDCOM_NAV_COMPONENT]);
        }
        $this->_request_data['topics'][$node[MIDCOM_NAV_FULLURL]] = "{$prefix}{$node[MIDCOM_NAV_NAME]}";

        // Recurse
        $prefix .= "{$node[MIDCOM_NAV_NAME]} &rsaquo; ";
        $subnodes = $nap->list_nodes($node_id);
        foreach ($subnodes as $sub_id) {
            $this->search_nodes($sub_id, $nap, $prefix);
        }
    }

    /**
     * Expand arrays of custom rules to end of query
     *
     * @param string $final_query reference to the query string to be passed on to the indexer.
     * @param mixed $terms array or string to append
     */
    private function append_terms_recursive(string &$final_query, $terms)
    {
        if (is_array($terms)) {
            foreach ($terms as $term) {
                $this->append_terms_recursive($final_query, $term);
            }
        } elseif (is_string($terms)) {
            $final_query .= $terms;
        } else {
            debug_add('Don\'t know how to handle terms of type: ' . gettype($terms), MIDCOM_LOG_ERROR);
            debug_print_r('$terms', $terms);
        }
    }

    /**
     * Queries the information from the index and prepares to display the result page.
     */
    public function _handler_result(Request $request, array &$data)
    {
        $this->prepare_query_data();
        // If we don't have a query string, relocate to empty search form
        if (!isset($_REQUEST['query'])) {
            debug_add('$_REQUEST["query"] is not set, relocating back to form', MIDCOM_LOG_INFO);
            $url = ($_REQUEST['type'] == 'basic') ? '' : 'advanced/';
            return new midcom_response_relocate($url);
        }
        $this->prepare_formdata($_REQUEST['type']);

        if (   count(explode(' ', $data['query'])) == 1
            && !str_contains($data['query'], '*')
            && $this->_config->get('single_term_auto_wildcard')) {
            //If there is only one search term append * to the query if auto_wildcard is enabled
            $data['query'] .= '*';
        }

        if ($data['type'] == 'basic') {
            $indexer = midcom::get()->indexer;
            $final_query = $data['query'];
            debug_add("Final query: {$final_query}");
            $result = $indexer->query($final_query);
        } elseif ($data['type'] == 'advanced') {
            $result = $this->do_advanced_query($data);
        } else {
            throw new midcom_error_notfound('unknown query type');
        }

        $this->process_results($result);
        $this->populate_toolbar($request);
    }

    private function populate_toolbar(Request $request)
    {
        $other_type = ($this->_request_data['type'] == 'advanced') ? 'basic' : 'advanced';
        $this->_request_data['params'] = '';
        if ($request->query->count() > 0) {
            $request->query->set('type', $other_type);
            $this->_request_data['params'] = '?' . $request->getQueryString();
        }

        $url = '';
        if ($this->_request_data['type'] == 'basic') {
            $url = 'advanced/';
        }

        $this->_view_toolbar->add_item([
            MIDCOM_TOOLBAR_URL => $url . $this->_request_data['params'],
            MIDCOM_TOOLBAR_LABEL => $this->_l10n->get($other_type . ' search'),
            MIDCOM_TOOLBAR_GLYPHICON => 'search',
        ]);
    }

    private function process_results(array $result)
    {
        $count = count($result);
        $this->_request_data['document_count'] = $count;

        if ($count == 0) {
            midcom::get()->cache->content->uncached();
        }

        if ($count > 0) {
            $results_per_page = $this->_config->get('results_per_page');
            $max_pages = ceil($count / $results_per_page);
            $page = min($_REQUEST['page'], $max_pages);
            $first_document_id = ($page - 1) * $results_per_page;
            $last_document_id = min(($count - 1), (($page * $results_per_page) - 1));

            $this->_request_data['page'] = $page;
            $this->_request_data['max_pages'] = $max_pages;
            $this->_request_data['first_document_number'] = $first_document_id + 1;
            $this->_request_data['last_document_number'] = $last_document_id + 1;
            $this->_request_data['shown_documents'] = $last_document_id - $first_document_id + 1;
            $this->_request_data['results_per_page'] = $results_per_page;
            $this->_request_data['all_results'] =& $result;
            $this->_request_data['result'] = array_slice($result, $first_document_id, $results_per_page);

            // Register GUIDs for cache engine
            foreach ($this->_request_data['result'] as $doc) {
                if (   !isset($doc->source)
                    || !mgd_is_guid($doc->source)) {
                    // Non-Midgard results don't need to go through cache registration
                    continue;
                }
                midcom::get()->cache->content->register($doc->source);
            }
            reset($this->_request_data['result']);
        }
    }

    /**
     * Sane defaults for REQUEST vars
     */
    private function prepare_query_data()
    {
        $defaults = [
            'type' => 'basic',
            'page' => 1,
            'component' => '',
            'topic' => '',
            'lastmodified' => 0
        ];

        $_REQUEST = array_merge($defaults, $_REQUEST);
    }

    private function do_advanced_query(array &$data) : array
    {
        $data['request_topic'] = trim($_REQUEST['topic']);
        $data['component'] = trim($_REQUEST['component']);
        $data['lastmodified'] = (integer) trim($_REQUEST['lastmodified']);
        $filter = new midcom_services_indexer_filter_chained;
        if ($data['lastmodified'] > 0) {
            $filter->add_filter(new midcom_services_indexer_filter_date('__EDITED', $data['lastmodified'], 0));
        }

        $final_query = '';
        if ($data['query'] != '') {
            $final_query = (midcom::get()->config->get('indexer_backend') == 'solr') ? $data['query'] : "({$data['query']})";
        }

        if ($data['request_topic'] != '') {
            $filter->add_filter(new midcom_services_indexer_filter_string('__TOPIC_URL', '"' . $data['request_topic'] . '*"'));
        }

        if ($data['component'] != '') {
            $filter->add_filter(new midcom_services_indexer_filter_string('__COMPONENT', $data['component']));
        }

        // Way to add very custom terms
        if (isset($_REQUEST['append_terms'])) {
            $this->append_terms_recursive($final_query, $_REQUEST['append_terms']);
        }

        debug_add("Final query: {$final_query}");
        $indexer = midcom::get()->indexer;

        if ($filter->count() == 0) {
            $filter = null;
        }
        return $indexer->query($final_query, $filter);
    }

    /**
     * Displays the resultset.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $data The local request data.
     */
    public function _show_result($handler_id, array &$data)
    {
        if ($data['document_count'] > 0) {
            midcom_show_style('results');
        } else {
            midcom_show_style('no_match');
        }
    }

    /**
     * Prepare OpenSearch data file for browser search bar integration.
     */
    public function _handler_opensearchdescription(array &$data)
    {
        midcom::get()->cache->content->content_type("application/opensearchdescription+xml; charset=UTF-8");
        midcom::get()->skip_page_style = true;

        $data['node'] = $this->_topic;
        return $this->show('opensearch_description');
    }
}
