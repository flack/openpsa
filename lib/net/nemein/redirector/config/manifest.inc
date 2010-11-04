'name' => 'net.nemein.redirector',
'icon' => 'stock-icons/16x16/stock_jump-to.png',
'purecode' => false,
'version' => '8.09.9',
'privileges' => array
(
    'noredirect' => MIDCOM_PRIVILEGE_DENY,
),
'class_definitions' => array
(
    'midcom_dba_classes.inc',
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
