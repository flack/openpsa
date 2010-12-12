'feeds_list' => Array
(
    'handler' => Array('net_nemein_rss_manage', 'list'),
    'fixed_args' => array('feeds', 'list'),
),
'feeds_opml' => Array
(
    'handler' => Array('net_nemein_rss_manage', 'opml'),
    'fixed_args' => array('feeds.opml'),
),
'feeds_subscribe' => Array
(
    'handler' => Array('net_nemein_rss_manage', 'subscribe'),
    'fixed_args' => array('feeds', 'subscribe'),
),
'feeds_edit' => Array
(
    'handler' => Array('net_nemein_rss_manage', 'edit'),
    'fixed_args' => array('feeds', 'edit'),
    'variable_args' => 1,
),
'feeds_delete' => Array
(
    'handler' => Array('net_nemein_rss_manage', 'delete'),
    'fixed_args' => array('feeds', 'delete'),
    'variable_args' => 1,
),
'feeds_fetch_all' => Array
(
    'handler' => Array('net_nemein_rss_manage', 'fetch'),
    'fixed_args' => array('feeds', 'fetch', 'all'),
),
'feeds_fetch' => Array
(
    'handler' => Array('net_nemein_rss_manage', 'fetch'),
    'fixed_args' => array('feeds', 'fetch'),
    'variable_args' => 1,
),
