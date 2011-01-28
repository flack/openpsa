'name' => 'net.nehmer.buddylist',
'icon' => 'stock-icons/16x16/stock_people.png',
'version' => '9.0pre',
'class_mapping' => array
(
    'net_nehmer_buddylist_entry_db' => 'net_nehmer_buddylist_entry'
),
'watches' => array
(
    array
    (
        'classes' => array
        (
            'midgard_person',
        ),
        'operations' => MIDCOM_OPERATION_DBA_DELETE,
    ),
),
'package.xml' => array
(
    'summary' => 'Buddy list manager',
    'description' => 'Buddy list and social network manager',
    'maintainers' => array
    (
        'torben' => array
        (
            'name' => 'Torben Nehmer',
            'email' => 'torben@nehmer.net',
            'role' => 'lead',
            'active' => 'no',
        ),
        'rambo' => array
        (
            'name' => 'Eero af Heurlin',
            'email' => 'eero.afheurlin@iki.fi',
            'role' => 'lead',
        ),
    ),
    'dependencies' => array
    (
        'midcom' => array(),
        'org.openpsa.qbpager' => array(),
    ),
),
'state' => 'stable',
