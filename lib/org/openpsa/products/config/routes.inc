'config' => Array
(
    'handler' => array ('org_openpsa_products_handler_configuration', 'config'),
    'fixed_args' => array ('config'),
),
'config_recreate' => Array
(
    'handler' => array ('org_openpsa_products_handler_configuration', 'recreate'),
    'fixed_args' => array ('config', 'recreate'),
),

// Handle /search/
'view_search_redirect' => Array
(
    'handler' => Array('org_openpsa_products_handler_product_search', 'search_redirect'),
    'fixed_args' => Array('search'),
),

// Handle /search/<product schema>
'view_search' => Array
(
    'handler' => Array('org_openpsa_products_handler_product_search', 'search'),
    'fixed_args' => Array('search'),
    'variable_args' => 1,
),

// Handle /search/raw/<product schema>
'view_search_raw' => Array
(
    'handler' => Array('org_openpsa_products_handler_product_search', 'search'),
    'fixed_args' => Array('search', 'raw'),
    'variable_args' => 1,
),

// Handle / (might be changed by config option search_first)
'index' => Array
(
    'handler' => Array('org_openpsa_products_handler_group_list', 'list'),
),

// Handle /<group guid>
'list' => Array
(
    'handler' => Array('org_openpsa_products_handler_group_list', 'list'),
    'variable_args' => 1,
),

// Handle /groupsblock/<productgroup>/
'groupsblock' => Array
(
    'handler' => Array('org_openpsa_products_handler_group_groupsblock', 'groupsblock'),
    'fixed_args' => Array('groupsblock'),
    'variable_args' => 2,
),

// Handle /edit/<product_group guid>
'edit_product_group' => Array
(
    'handler' => Array('org_openpsa_products_handler_group_edit', 'edit'),
    'fixed_args' => Array('edit'),
    'variable_args' => 1,
),

// Handle /create/<group id>/<schema name>
'create_group' => Array
(
    'handler' => Array('org_openpsa_products_handler_group_create', 'create'),
    'fixed_args' => Array('create'),
    'variable_args' => 2,
),

// Handle /import/group/csv
'import_group_csv' => Array
(
    'handler' => Array('org_openpsa_products_handler_group_csvimport', 'csv_select'),
    'fixed_args' => Array('import', 'group', 'csv'),
),

// Handle /import/group/csv2
'import_group' => Array
(
    'handler' => Array('org_openpsa_products_handler_group_csvimport', 'csv'),
    'fixed_args' => Array('import', 'group', 'csv2'),
),

// Handle /product/create/<schema name>
'create_product' => Array
(
    'handler' => Array('org_openpsa_products_handler_product_create', 'create'),
    'fixed_args' => Array('product', 'create'),
    'variable_args' => 1,
),

// Handle /product/create/<group id>/<schema name>
'create_group_product' => Array
(
    'handler' => Array('org_openpsa_products_handler_product_create', 'create'),
    'fixed_args' => Array('product', 'create'),
    'variable_args' => 2,
),

// Handle /import/product/csv
'import_product_csv' => Array
(
    'handler' => Array('org_openpsa_products_handler_product_csvimport', 'csv_select'),
    'fixed_args' => Array('import', 'product', 'csv'),
),

// Handle /import/group/csv2
'import_product' => Array
(
    'handler' => Array('org_openpsa_products_handler_product_csvimport', 'csv'),
    'fixed_args' => Array('import', 'product', 'csv2'),
),

// Handle /product/edit/<product guid>
'edit_product' => Array
(
    'handler' => Array('org_openpsa_products_handler_product_crud', 'update'),
    'fixed_args' => Array('product', 'edit'),
    'variable_args' => 1,
),

// Handle /product/delete/<product guid>
'delete_product' => Array
(
    'handler' => Array('org_openpsa_products_handler_product_crud', 'delete'),
    'fixed_args' => Array('product', 'delete'),
    'variable_args' => 1,
),

// Handle /product/<product guid>
'view_product' => Array
(
    'handler' => Array('org_openpsa_products_handler_product_view', 'view'),
    'fixed_args' => Array('product'),
    'variable_args' => 1,
),

// Handle /product/raw/<product guid>
'view_product_raw' => Array
(
    'handler' => Array('org_openpsa_products_handler_product_view', 'view'),
    'fixed_args' => Array('product', 'raw'),
    'variable_args' => 1,
),

// Handle /updated/<N>
'updated_products' => Array
(
    'handler' => Array('org_openpsa_products_handler_product_latest', 'updated'),
    'fixed_args' => Array('updated'),
    'variable_args' => 1,
),

// Handle /updated/<product group>/<N>
'updated_products_intree' => Array
(
    'handler' => Array('org_openpsa_products_handler_product_latest', 'updated'),
    'fixed_args' => Array('updated'),
    'variable_args' => 2,
),

// Handle /rss.xml
'updated_products_feed' => Array
(
    'handler' => Array('org_openpsa_products_handler_product_latest', 'feed'),
    'fixed_args' => Array('rss.xml'),
),

// Handle /rss/<productgroup>/rss.xml
'updated_products_feed_intree' => Array
(
    'handler' => Array('org_openpsa_products_handler_product_latest', 'feed'),
    'fixed_args' => Array('rss'),
    'variable_args' => 2,
),

// Handle /export/product/csv
'export_product_csv' => Array
(
    'handler' => Array('org_openpsa_products_handler_product_csv', 'csv'),
    'fixed_args' => Array('export', 'product', 'csv'),
),

// Handle /export/product/csv/<schema_name>
'export_product_csv_schemaname' => Array
(
    'handler' => Array('org_openpsa_products_handler_product_csv', 'csv'),
    'fixed_args' => Array('export', 'product', 'csv'),
    'variable_args' => 1,
),