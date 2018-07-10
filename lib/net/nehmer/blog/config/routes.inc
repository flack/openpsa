// Index
'index' => [
    'handler' => ['net_nehmer_blog_handler_index', 'index'],
],
'latest' => [
    'handler' => ['net_nehmer_blog_handler_index', 'index'],
    'fixed_args' => 'latest',
    'variable_args' => 1,
],

// Handler for /ajax/latest/<number>
'ajax-latest' => [
    'handler' => ['net_nehmer_blog_handler_index', 'index'],
    'fixed_args' => ['ajax', 'latest'],
    'variable_args' => 1,
],

// Handler for /category/<category>
'index-category' => [
    'handler' => ['net_nehmer_blog_handler_index', 'index'],
    'fixed_args' => 'category',
    'variable_args' => 1,
],
// Handler for /category/latest/<category>/<number>
'latest-category' => [
    'handler' => ['net_nehmer_blog_handler_index', 'index'],
    'fixed_args' => ['category', 'latest'],
    'variable_args' => 2,
],

// Various Feeds and their index page
'feed-index' => [
    'handler' => ['net_nehmer_blog_handler_feed', 'index'],
    'fixed_args' => 'feeds',
],
'feed-category-rss2' => [
    'handler' => ['net_nehmer_blog_handler_feed', 'feed'],
    'fixed_args' => ['feeds', 'category'],
    'variable_args' => 1,
],
'feed-rss2' => [
    'handler' => ['net_nehmer_blog_handler_feed', 'feed'],
    'fixed_args' => 'rss.xml',
],
'feed-rss1' => [
    'handler' => ['net_nehmer_blog_handler_feed', 'feed'],
    'fixed_args' => 'rss1.xml',
],
'feed-rss091' => [
    'handler' => ['net_nehmer_blog_handler_feed', 'feed'],
    'fixed_args' => 'rss091.xml',
],
'feed-atom' => [
    'handler' => ['net_nehmer_blog_handler_feed', 'feed'],
    'fixed_args' => 'atom.xml',
],

// The Archive
'archive-welcome' => [
    'handler' => ['net_nehmer_blog_handler_archive', 'welcome'],
    'fixed_args' => 'archive',
],
'archive-year' => [
    'handler' => ['net_nehmer_blog_handler_archive', 'list'],
    'fixed_args' => ['archive', 'year'],
    'variable_args' => 1,
    'validation' => [0 => ['is_numeric']]
],
'archive-year-category' => [
    'handler' => ['net_nehmer_blog_handler_archive', 'list'],
    'fixed_args' => ['archive', 'year'],
    'variable_args' => 2,
    'validation' => [0 => ['is_numeric']]
],
'archive-month' => [
    'handler' => ['net_nehmer_blog_handler_archive', 'list'],
    'fixed_args' => ['archive', 'month'],
    'variable_args' => 2,
    'validation' => [0 => ['is_numeric'], 1 => ['is_numeric']]
],

// Administrative stuff
'edit' => [
    'handler' => ['net_nehmer_blog_handler_admin', 'edit'],
    'fixed_args' => 'edit',
    'variable_args' => 1,
],
'delete' => [
    'handler' => ['net_nehmer_blog_handler_admin', 'delete'],
    'fixed_args' => 'delete',
    'variable_args' => 1,
],
'create' => [
    'handler' => ['net_nehmer_blog_handler_create', 'create'],
    'fixed_args' => 'create',
    'variable_args' => 1,
],

'config' => [
    'handler' => ['net_nehmer_blog_handler_configuration', 'config'],
    'fixed_args' => 'config',
],

'config_recreate' => [
    'handler' => ['net_nehmer_blog_handler_configuration', 'recreate'],
    'fixed_args' => ['config', 'recreate'],
],

// View article (might be changed by config setting view_in_url)
'view-raw' => [
    'handler' => ['net_nehmer_blog_handler_view', 'view'],
    'fixed_args' => 'raw',
    'variable_args' => 1,
],
'view' => [
    'handler' => ['net_nehmer_blog_handler_view', 'view'],
    'variable_args' => 1,
],
