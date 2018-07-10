'name' => 'net.nemein.redirector',
'icon' => 'arrow-right',
'purecode' => false,
'privileges' => [
    'noredirect' => MIDCOM_PRIVILEGE_DENY,
],
'class_mapping' => [
    'net_nemein_redirector_tinyurl' => 'net_nemein_redirector_tinyurl_dba',
],
'description' => 'Folder that redirects to subnode or given URL',
