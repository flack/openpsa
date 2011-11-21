// Default search form, no args, Basic search from
'basic' => array
(
    'handler' => array('midcom_helper_search_handler_search', 'searchform')
),

// Resultlists, controlled using HTTP GET/POST
'result' => array
(
    'fixed_args' => 'result',
    'handler' => array('midcom_helper_search_handler_search', 'result')
),

// Advanced search form, no args
'advanced' => array
(
    'fixed_args' => 'advanced',
    'handler' => array('midcom_helper_search_handler_search', 'searchform')
),

// OpenSearch description file
'opensearch_description' => array
(
    'fixed_args' => 'opensearch.xml',
    'handler' => array('midcom_helper_search_handler_search', 'opensearchdescription')
),
