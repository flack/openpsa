// Schema database to use
'schemadb' => 'file:/net/nehmer/static/config/schemadb_default.inc',
'schemadbs' => Array
(
    'file:/net/nehmer/static/config/schemadb_default.inc' => 'Default Schema Database',
),

'schemadb_link' => 'file:/net/nehmer/static/config/schemadb_link.inc',
'schemadb_config' => 'file:/net/nehmer/static/config/config_schemadb.inc',

// A valid sort order usable with QB
'sort_order' => 'metadata.score',

// Generate a directory index if no index article is found?
'autoindex' => false,
'show_blobs_in_autoindex' => true,

// If not in autoindex mode, show the index article in NAP?
'indexinnav' => false,

// Hide all the leaves from navigation
'hide_navigation' => false,

// Automatically approve new articles ?
'auto_approve' => false,

// Set this to false if you want all users to have full control over URL names.
'simple_name_handling' => true,

// Set this to true if you want to enable on-site AJAX editing
'enable_ajax_editing' => midcom::get()->config->get('enable_ajax_editing'),

// Page linking system
'enable_article_links' => false,

// Creation callback snippet for defining site-specific function
'callback_snippet' => null,

// Callback function will be called after creation e.g. to provide interface for page linking system
// $callback_function($article, $topic)
'callback_function' => null,

'folder_in_title' => true,