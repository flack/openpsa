'feeds_list' => [
    'handler' => ['net_nemein_rss_handler_list', 'list'],
    'fixed_args' => ['list'],
],
'feeds_opml' => [
    'handler' => ['net_nemein_rss_handler_list', 'opml'],
    'fixed_args' => ['feeds.opml'],
],
'feeds_subscribe' => [
    'handler' => ['net_nemein_rss_handler_admin', 'subscribe'],
    'fixed_args' => ['subscribe'],
],
'feeds_edit' => [
    'handler' => ['net_nemein_rss_handler_admin', 'edit'],
    'fixed_args' => ['edit'],
    'variable_args' => 1,
],
'feeds_delete' => [
    'handler' => ['net_nemein_rss_handler_admin', 'delete'],
    'fixed_args' => ['delete'],
    'variable_args' => 1,
],
'feeds_fetch_all' => [
    'handler' => ['net_nemein_rss_handler_fetch', 'fetch'],
    'fixed_args' => ['fetch', 'all'],
],
'feeds_fetch' => [
    'handler' => ['net_nemein_rss_handler_fetch', 'fetch'],
    'fixed_args' => ['fetch'],
    'variable_args' => 1,
],
