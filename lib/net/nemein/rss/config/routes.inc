'feeds_list' => Array
(
    'handler' => Array('net_nemein_rss_handler_list', 'list'),
    'fixed_args' => array('list'),
),
'feeds_opml' => Array
(
    'handler' => Array('net_nemein_rss_handler_list', 'opml'),
    'fixed_args' => array('feeds.opml'),
),
'feeds_subscribe' => Array
(
    'handler' => Array('net_nemein_rss_handler_admin', 'subscribe'),
    'fixed_args' => array('subscribe'),
),
'feeds_edit' => Array
(
    'handler' => Array('net_nemein_rss_handler_admin', 'edit'),
    'fixed_args' => array('edit'),
    'variable_args' => 1,
),
'feeds_delete' => Array
(
    'handler' => Array('net_nemein_rss_handler_admin', 'delete'),
    'fixed_args' => array('delete'),
    'variable_args' => 1,
),
'feeds_fetch_all' => Array
(
    'handler' => Array('net_nemein_rss_handler_fetch', 'fetch'),
    'fixed_args' => array('fetch', 'all'),
),
'feeds_fetch' => Array
(
    'handler' => Array('net_nemein_rss_handler_fetch', 'fetch'),
    'fixed_args' => array('fetch'),
    'variable_args' => 1,
),
