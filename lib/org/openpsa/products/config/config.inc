/**
 * This is the configuration file for the module.
 * This is where you set default values for different configuration variables
 */
/* The schema databases to use */
'schemadb_product' => 'file:/org/openpsa/products/config/schemadb_product_default.inc',
'schemadb_group' => 'file:/org/openpsa/products/config/schemadb_group_default.inc',
'schemadb_config' => 'file:/org/openpsa/products/config/config_schemadb.inc',

'unit_options' => Array
(
    'p'  => 'piece',     // per piece
    'h'  => 'hour',      // per hour
    'd'  => 'day',       // per day
    'm'  => 'month',     // per month
    'q'  => 'quarter',   // per quarter
    'hy' => 'half-year', // per half
    'y'  => 'year',      // per annum
),

'root_group' => 0,

'code_in_title' => false,

// Indexer related
'index_products' => true, // wheter to index products
'index_groups' => true, // wheter to index groups

// How to list groups when browsing groups
'groups_listing_order' => array('code', 'title'),

// How to list products when browsing groups
'products_listing_order' => array('code', 'title'),
'enable_scheduling' => false,

'group_list_products' => true,
'products_per_page' => 30,
'show_items_in_feed' => 10,

// default schema to use when exporting products
'csv_export_schema' => 'default',

// default schema to use when importing products
'csv_import_schema' => 'default',

// Use custom rss feeds
'custom_rss_feeds' => false, // array('title' => 'http://example.com')

//When using the subgroups sidebar, you can disable the subgroups block on the frontpage.
'disable_subgroups_on_frontpage' => false,

// if we have subgroups and products in group, what shall we show. Allowed options 'products' and 'groups'.
'listing_primary' => 'products',

'import_csv_data_percentage' => 0,

'export_skip_fields' => '',
'import_skip_fields' => 'productGroup', // comma separated, note that not skipping productGroup is asking for trouble
// These are used to detect charset of imported data and convert to local
'mb_detect_encoding_list' => 'ASCII,JIS,UTF-8,ISO-8859-1,EUC-JP,SJIS',
'iconv_append_target' => '//TRANSLIT', //Transliterate by default (other valid options '//IGNORE' or null)

'product_page_title' => '<TOPIC_TITLE>: <PRODUCT_TITLE>',