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
'index_products' => true, // whether to index products
'index_groups' => true, // whether to index groups

'enable_scheduling' => false,

'show_items_in_feed' => 10,

// default schema to use when exporting products
'csv_export_schema' => 'default',

// default schema to use when importing products
'csv_import_schema' => 'default',

// Use custom rss feeds
'custom_rss_feeds' => false, // array('title' => 'http://example.com')

'import_csv_data_percentage' => 0,

'export_skip_fields' => '',
'import_skip_fields' => 'productGroup', // comma separated, note that not skipping productGroup is asking for trouble
// These are used to detect charset of imported data and convert to local
'mb_detect_encoding_list' => 'ASCII,JIS,UTF-8,ISO-8859-1,EUC-JP,SJIS',
'iconv_append_target' => '//TRANSLIT', //Transliterate by default (other valid options '//IGNORE' or null)

'product_page_title' => '<TOPIC_TITLE>: <PRODUCT_TITLE>',