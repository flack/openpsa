// Default search form, no args, Basic search from
'basic' => [
    'handler' => ['midcom_helper_search_handler_search', 'searchform']
],

// Resultlists, controlled using HTTP GET/POST
'result' => [
    'fixed_args' => 'result',
    'handler' => ['midcom_helper_search_handler_search', 'result']
],

// Advanced search form, no args
'advanced' => [
    'fixed_args' => 'advanced',
    'handler' => ['midcom_helper_search_handler_search', 'searchform']
],

// OpenSearch description file
'opensearch_description' => [
    'fixed_args' => 'opensearch.xml',
    'handler' => ['midcom_helper_search_handler_search', 'opensearchdescription']
],
