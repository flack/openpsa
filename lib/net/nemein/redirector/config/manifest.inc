'name' => 'net.nemein.redirector',
'icon' => 'stock-icons/16x16/stock_jump-to.png',
'purecode' => false,
'privileges' => array
(
    'noredirect' => MIDCOM_PRIVILEGE_DENY,
),
'class_mapping' => array
(
    'net_nemein_redirector_tinyurl' => 'net_nemein_redirector_tinyurl_dba',
),
'description' => 'Folder that redirects to subnode or given URL',
