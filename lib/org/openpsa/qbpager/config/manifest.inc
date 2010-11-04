'name' => 'org.openpsa.qbpager',
'icon' => 'org.openpsa.qbpager/pager.png',
'purecode' => true,
'privileges' => array(),
'class_definitions' => array(),
'version' => '8.09.9',
'state' => 'stable',
'package.xml' => array
(
    'summary' => 'Paged Query-Builder library',
    'description' => 'Library for automatically splitting query builder results to multiple pages',
    'maintainers' => array
    (
        'rambo' => array
        (
            'name' => 'Eero af Heurlin',
            'email' => 'eero.afheurlin@iki.fi',
            'role' => 'lead',
        ),
        'neithan' => array
        (
            'name' => 'Lauri Manner',
            'email' => 'neithan@nemein.com',
            'role' => 'developer',
        ),
    ),
    'dependencies' => array
    (
        'midcom' => array(),
        'midcom.helper.xsspreventer' => array(),
    ),
),
