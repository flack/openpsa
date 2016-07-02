'name' => 'net.nemein.redirector',
'icon' => 'stock-icons/16x16/stock_jump-to.png',
'purecode' => false,
'version' => '9.0.0+git',
'privileges' => array
(
    'noredirect' => MIDCOM_PRIVILEGE_DENY,
),
'class_mapping' => array
(
    'net_nemein_redirector_tinyurl' => 'net_nemein_redirector_tinyurl_dba',
),
'package.xml' => array
(
    'summary' => 'Redirector',
    'description' => 'Folder that redirects to subnode or given URL',
    'maintainers' => array
    (
        'bergie' => array
        (
            'name' => 'Henri Bergius',
            'email' => 'henri.bergius@iki.fi',
            'role' => 'lead',
        ),
    ),
    'dependencies' => array
    (
        'midcom' => array(),
    ),
),
'state' => 'stable',
