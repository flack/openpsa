'search_help_message' => 'lucene',
'results_per_page' => 10,
/**
 * Whether to automatically add * to single term queries
 * Warning: may lead to unintuitive matches when user enters
 * whole keyword (instead of just part of it) as the single term
 * (objects containing just the given keyword as written
 * may not get matched since the wildcard part matches nothing)
 */
'single_term_auto_wildcard' => false,

'schemadb_config' => 'file:/midcom/helper/search/config/config_schemadb.php',
