// Default search form, no args, Basic search from
'basic' => array
(
    'handler' => 'searchform'
),
    
// Resultlists, controlled using HTTP GET/POST
'result' => array
(
    'fixed_args' => 'result',
    'handler' => 'result'
),
    
// Advanced search form, no args
'advanced' => array
(
    'fixed_args' => 'advanced',
    'handler' => 'searchform'
),
    
// OpenSearch description file
'opensearch_description' => array
(
    'fixed_args' => 'opensearch.xml',
    'handler' => 'opensearchdescription'
),
