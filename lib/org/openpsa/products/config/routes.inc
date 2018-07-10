'config' => [
    'handler' => ['org_openpsa_products_handler_configuration', 'config'],
    'fixed_args' => ['config'],
],
'config_recreate' => [
    'handler' => ['org_openpsa_products_handler_configuration', 'recreate'],
    'fixed_args' => ['config', 'recreate'],
],

// Handle /
'list' => [
    'handler' => ['org_openpsa_products_handler_list', 'list'],
],

// Handle /json/
'list_json' => [
    'handler' => ['org_openpsa_products_handler_list', 'json'],
    'fixed_args' => ['json'],
],

// Handle /json/<group guid>
'list_json_group' => [
    'handler' => ['org_openpsa_products_handler_list', 'json'],
    'fixed_args' => ['json'],
    'variable_args' => 1
],

// Handle /<group guid>
'list_group' => [
    'handler' => ['org_openpsa_products_handler_list', 'list'],
    'variable_args' => 1,
],

// Handle /edit/<product_group guid>
'edit_product_group' => [
    'handler' => ['org_openpsa_products_handler_group_edit', 'edit'],
    'fixed_args' => ['edit'],
    'variable_args' => 1,
],

// Handle /create/<group id>/<schema name>
'create_group' => [
    'handler' => ['org_openpsa_products_handler_group_create', 'create'],
    'fixed_args' => ['create'],
    'variable_args' => 2,
],

// Handle /product/create/<schema name>
'create_product' => [
    'handler' => ['org_openpsa_products_handler_product_create', 'create'],
    'fixed_args' => ['product', 'create'],
    'variable_args' => 1,
],

// Handle /product/create/<group id>/<schema name>
'create_group_product' => [
    'handler' => ['org_openpsa_products_handler_product_create', 'create'],
    'fixed_args' => ['product', 'create'],
    'variable_args' => 2,
],

// Handle /product/edit/<product guid>
'edit_product' => [
    'handler' => ['org_openpsa_products_handler_product_admin', 'update'],
    'fixed_args' => ['product', 'edit'],
    'variable_args' => 1,
],

// Handle /product/delete/<product guid>
'delete_product' => [
    'handler' => ['org_openpsa_products_handler_product_admin', 'delete'],
    'fixed_args' => ['product', 'delete'],
    'variable_args' => 1,
],

// Handle /product/<product guid>
'view_product' => [
    'handler' => ['org_openpsa_products_handler_product_view', 'view'],
    'fixed_args' => ['product'],
    'variable_args' => 1,
],

// Handle /product/raw/<product guid>
'view_product_raw' => [
    'handler' => ['org_openpsa_products_handler_product_view', 'view'],
    'fixed_args' => ['product', 'raw'],
    'variable_args' => 1,
],

// Handle /updated/<N>
'updated_products' => [
    'handler' => ['org_openpsa_products_handler_product_latest', 'updated'],
    'fixed_args' => ['updated'],
    'variable_args' => 1,
],

// Handle /updated/<product group>/<N>
'updated_products_intree' => [
    'handler' => ['org_openpsa_products_handler_product_latest', 'updated'],
    'fixed_args' => ['updated'],
    'variable_args' => 2,
],

// Handle /rss.xml
'updated_products_feed' => [
    'handler' => ['org_openpsa_products_handler_product_latest', 'feed'],
    'fixed_args' => ['rss.xml'],
],

// Handle /rss/<productgroup>/rss.xml
'updated_products_feed_intree' => [
    'handler' => ['org_openpsa_products_handler_product_latest', 'feed'],
    'fixed_args' => ['rss'],
    'variable_args' => 2,
],

// Handle /export/product/csv
'export_product_csv' => [
    'handler' => ['org_openpsa_products_handler_product_csv', 'csv'],
    'fixed_args' => ['export', 'product', 'csv'],
],

// Handle /export/product/csv/<schema_name>
'export_product_csv_schemaname' => [
    'handler' => ['org_openpsa_products_handler_product_csv', 'csv'],
    'fixed_args' => ['export', 'product', 'csv'],
    'variable_args' => 1,
],