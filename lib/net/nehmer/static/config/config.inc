// Schema database to use
'schemadb' => 'file:/net/nehmer/static/config/schemadb_default.inc',
'schemadbs' => [
    'file:/net/nehmer/static/config/schemadb_default.inc' => 'Default Schema Database',
],

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

// Set this to false if you want all users to have full control over URL names.
'simple_name_handling' => true,

// Callback function will be called after creation
// $callback_function($article, $topic)
'callback_function' => null,

'folder_in_title' => true,