// Match /delete/<wikipage>
'delete' => array
(
    'handler' => Array('net_nemein_wiki_handler_delete', 'delete'),
    'fixed_args' => 'delete',
    'variable_args' => 1,
),

// Match /raw/<wikipage>
'raw' => array
(
    'handler' => Array('net_nemein_wiki_handler_view', 'raw'),
    'fixed_args' => 'raw',
    'variable_args' => 1,
),

// Match /whatlinks/<wikipage>
'whatlinks' => array
(
    'handler' => Array('net_nemein_wiki_handler_view', 'whatlinks'),
    'fixed_args' => 'whatlinks',
    'variable_args' => 1,
),

// Match /subscribe/<wikipage>
'subscribe' => array
(
    'handler' => Array('net_nemein_wiki_handler_view', 'subscribe'),
    'fixed_args' => 'subscribe',
    'variable_args' => 1,
),

// Match /create/
'create_by_word' => array
(
    'handler' => Array('net_nemein_wiki_handler_create', 'create'),
    'fixed_args' => 'create',
),

// Match /create/<schema>
'create_by_word_schema' => array
(
    'handler' => Array('net_nemein_wiki_handler_create', 'create'),
    'fixed_args' => 'create',
    'variable_args' => 1,
),

// Match /notfound/<wikiword>
'notfound' => array
(
    'handler' => Array('net_nemein_wiki_handler_notfound', 'notfound'),
    'fixed_args' => 'notfound',
    'variable_args' => 1,
),

// Match /edit/<wikipage>
'edit' => array
(
    'handler' => Array('net_nemein_wiki_handler_edit', 'edit'),
    'fixed_args' => 'edit',
    'variable_args' => 1,
),

// Match /change/<wikipage>
'change' => array
(
    'handler' => Array('net_nemein_wiki_handler_edit', 'change'),
    'fixed_args' => 'change',
    'variable_args' => 1,
),

// Match /rss.xml
'rss' => array
(
    'handler' => Array('net_nemein_wiki_handler_feed', 'rss'),
    'fixed_args' => 'rss.xml',
),

// Match /latest
'latest' => array
(
    'handler' => Array('net_nemein_wiki_handler_latest', 'latest'),
    'fixed_args' => 'latest',
),

// Match /orphans
'orphans' => array
(
    'handler' => Array('net_nemein_wiki_handler_orphan', 'orphan'),
    'fixed_args' => 'orphans',
),

// Match /
'start' => array
(
    'handler' => Array('net_nemein_wiki_handler_view', 'view'),
),

// Match /<wikipage>
'view' => array
(
    'handler' => Array('net_nemein_wiki_handler_view', 'view'),
    'variable_args' => 1,
),
