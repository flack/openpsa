<?php
/**
 * @package midcom.helper.search
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * MidCOM Indexer Front-End, Viewer Class
 *
 * @package midcom.helper.search
 */
class midcom_helper_search_viewer extends midcom_baseclasses_components_request
{
    public function _on_handle($handler_id, $args)
    {
        $_MIDCOM->add_link_head
        (
            array
            (
                'rel'   => 'search',
                'type'  => 'application/opensearchdescription+xml',
                'title' => $this->_topic->extra,
                'href'  => $_MIDCOM->get_host_name() . $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . 'opensearch.xml',
            )
        );
    }

    /**
     * Search form handler, nothing to do here.
     *
     * It uses the handler ID to distinguish between basic and advanced search forms.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_searchform($handler_id, array $args, array &$data)
    {
        switch ($handler_id)
        {
            case 'basic':
                $data['query'] = (array_key_exists('query', $_REQUEST) ? $_REQUEST['query'] : '');
                break;

            case 'advanced':
                $data['query'] = (array_key_exists('query', $_REQUEST) ? $_REQUEST['query'] : '');
                $data['request_topic'] = (array_key_exists('topic', $_REQUEST) ? $_REQUEST['topic'] : '');
                $data['component'] = (array_key_exists('component', $_REQUEST) ? $_REQUEST['component'] : '');
                $data['lastmodified'] = (array_key_exists('lastmodified', $_REQUEST) ? ((integer) $_REQUEST['lastmodified']) : 0);

                $data['topics'] = array('' => $this->_l10n->get('search anywhere'));
                $data['components'] = array('' => $this->_l10n->get('search all content types'));

                $nap = new midcom_helper_nav();
                $this->_search_nodes($nap->get_root_node(), $nap, '');
                break;
        }
        $data['type'] = $handler_id;
    }

    /**
     * Prepare the topic and component listings, this is a bit work intensive though,
     * we need to traverse everything.
     */
    private function _search_nodes($node_id, &$nap, $prefix)
    {
        $node = $nap->get_node($node_id);

        if (   ! array_key_exists($node[MIDCOM_NAV_COMPONENT], $this->_request_data['components'])
            && $node[MIDCOM_NAV_COMPONENT] != 'midcom.helper.search')
        {
            $i18n = $_MIDCOM->get_service('i18n');
            $l10n = $i18n->get_l10n($node[MIDCOM_NAV_COMPONENT]);
            $this->_request_data['components'][$node[MIDCOM_NAV_COMPONENT]] = $l10n->get($node[MIDCOM_NAV_COMPONENT]);
        }
        $this->_request_data['topics'][$node[MIDCOM_NAV_FULLURL]] = "{$prefix}{$node[MIDCOM_NAV_NAME]}";

        // Recurse
        $prefix .= "{$node[MIDCOM_NAV_NAME]} &rsaquo; ";
        $subnodes = $nap->list_nodes($node_id);
        foreach ($subnodes as $sub_id)
        {
            $this->_search_nodes($sub_id, $nap, $prefix);
        }
    }

    /**
     * Search form show handler, displays the search form, including
     * some hints about how to write queries.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_searchform($handler_id, array &$data)
    {
        midcom_show_style('search_form');
    }

    /**
     * Expand arrays of custom rules to end of query
     *
     * @param string &$final_query reference to the query string to be passed on to the indexer.
     * @param mixed $terms array or string to append
     */
    function append_terms_recursive(&$final_query, $terms)
    {
        if (is_array($terms))
        {
            foreach ($terms as $term)
            {
                $this->append_terms_recursive($final_query, $term);
            }
            return;
        }
        if (is_string($terms))
        {
            $final_query .= "{$terms}";
            return;
        }
        debug_add('Don\'t know how to handle terms of type: ' . gettype($terms), MIDCOM_LOG_ERROR);
        debug_print_r('$terms', $terms);
        return;
    }

    /**
     * Queries the information from the index and prepares to display the result page.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_result($handler_id, array $args, array &$data)
    {
        $this->_prepare_query_data();

        $data['type'] = $_REQUEST['type'];
        $data['query'] = trim($_REQUEST['query']);

        if (   count(explode(' ', $data['query'])) == 1
            && strpos($data['query'], '*') === false
            && $this->_config->get('single_term_auto_wilcard'))
        {
            //If there is only one search term append * to the query if auto_wildcard is enabled
            $data['query'] .= '*';
        }

        switch ($data['type'])
        {
            case 'basic':
                $indexer = $_MIDCOM->get_service('indexer');
                $final_query = $data['query'];
                debug_add("Final query: {$final_query}");
                $result = $indexer->query($final_query);
                break;

            case 'advanced':
                $result = $this->_do_advanced_query($data);
                break;
        }

        if ($result === false)
        {
            // Error while searching, we ignore this silently, as this is usually
            // a broken query. We don't have yet a way to pass error messages from
            // the indexer backend though (what would I give for a decent exception
            // handling here...)
            debug_add('Got boolean false as resultset (likely broken query), casting to empty array', MIDCOM_LOG_WARN);
            $result = Array();
        }

        $count = count($result);
        $data['document_count'] = $count;

        if ($data['document_count'] == 0)
        {
            $_MIDCOM->cache->content->uncached();
        }

        if ($count > 0)
        {
            $results_per_page = $this->_config->get('results_per_page');
            $max_pages = ceil($count / $results_per_page);
            $page = min($_REQUEST['page'], $max_pages);
            $first_document_id = ($page - 1) * $results_per_page;
            $last_document_id = min(($count - 1), (($page * $results_per_page) - 1));

            $data['page'] = $page;
            $data['max_pages'] = $max_pages;
            $data['first_document_number'] = $first_document_id + 1;
            $data['last_document_number'] = $last_document_id + 1;
            $data['shown_documents'] = $last_document_id - $first_document_id + 1;
            $data['results_per_page'] = $results_per_page;
            $data['all_results'] =& $result;
            $data['result'] = array_slice($result, $first_document_id, $results_per_page);

            // Register GUIDs for cache engine
            foreach($data['result'] as $doc)
            {
                if (   !isset($doc->source)
                    || !mgd_is_guid($doc->source))
                {
                    // Non-Midgard results don't need to go through cache registration
                    continue;
                }
                $_MIDCOM->cache->content->register($doc->source);
            }
            reset($data['result']);
        }
    }

    /**
     * Sane defaults for REQUEST vars
     */
    private function _prepare_query_data()
    {
        // If we don't have a query string, relocate to empty search form
        if (!isset($_REQUEST['query']))
        {
            debug_add('$_REQUEST["query"] is not set, relocating back to form', MIDCOM_LOG_INFO);
            if ($this->_request_data['type'] == 'basic')
            {
                $_MIDCOM->relocate('');
            }
            $_MIDCOM->relocate('advanced/');
        }
        if (!isset($_REQUEST['type']))
        {
            $_REQUEST['type'] = 'basic';
        }
        if (!isset($_REQUEST['page']))
        {
            $_REQUEST['page'] = 1;
        }
        if (!isset($_REQUEST['component']))
        {
            $_REQUEST['component'] = '';
        }
        if (!isset($_REQUEST['topic']))
        {
            $_REQUEST['topic'] = '';
        }
        if (!isset($_REQUEST['lastmodified']))
        {
            $_REQUEST['lastmodified'] = 0;
        }
    }

    private function _do_advanced_query(&$data)
    {
        $data['request_topic'] = trim($_REQUEST['topic']);
        $data['component'] = trim($_REQUEST['component']);
        $data['lastmodified'] = (integer) trim($_REQUEST['lastmodified']);
        if ($data['lastmodified'] > 0)
        {
            $filter = new midcom_services_indexer_filter_date('__EDITED', $data['lastmodified'], 0);
        }
        else
        {
            $filter = null;
        }

        if ($data['query'] != '' )
        {
            $final_query = ( $GLOBALS['midcom_config']['indexer_backend'] == 'solr' ) ? $data['query'] : "({$data['query']})";
        }
        else
        {
            $final_query = '';
        }

        if ($data['request_topic'] != '')
        {
            if ($final_query != '')
            {
                $final_query .= ' AND ';
            }
            $final_query .= "__TOPIC_URL:\"{$data['request_topic']}*\"";
        }

        if ($data['component'] != '')
        {
            if ($final_query != '')
            {
                $final_query .= ' AND ';
            }
            $final_query .= "__COMPONENT:{$data['component']}";
        }

        // Way to add very custom terms
        if (isset($_REQUEST['append_terms']))
        {
            $this->append_terms_recursive($final_query, $_REQUEST['append_terms']);
        }

        debug_add("Final query: {$final_query}");
        $indexer = $_MIDCOM->get_service('indexer');

        return $indexer->query($final_query, $filter);
    }

    /**
     * Displays the resultset.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_result($handler_id, array &$data)
    {
        if ($data['document_count'] > 0)
        {
            midcom_show_style('results');
        }
        else
        {
            midcom_show_style('no_match');
        }
    }

    /**
     * Prepare OpenSearch data file for browser search bar integration.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_opensearchdescription($handler_id, array $args, array &$data)
    {
        $_MIDCOM->cache->content->content_type("application/opensearchdescription+xml");
        $_MIDCOM->header("Content-type: application/opensearchdescription+xml; charset=UTF-8");
        $_MIDCOM->skip_page_style = true;
    }

    /**
     * Display OpenSearch data file for browser search bar integration.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_opensearchdescription($handler_id, array &$data)
    {
        $data['node'] = $this->_topic;
        midcom_show_style('opensearch_description');
    }
}
?>