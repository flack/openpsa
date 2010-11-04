'name' => 'midcom.helper.search',
'icon' => 'stock-icons/16x16/search.png',
'version' => '8.09.9',
'state' => 'stable',
'purecode' => false,
'privileges' => array(),
'class_definitions' => array(),
'package.xml' => array
(
    'summary' => 'Search tool',
    'description' => 'Front-end for the Lucene search engine',
    'maintainers' => array
    (
        'torben' => array
        (
            'name' => 'Torben Nehmer',
            'email' => 'torben@nehmer.net',
            'role' => 'lead',
            'active' => 'no',
        ),
        'tarjei' => array
        (
            'name' => 'Tarjei Huse',
            'email' => 'tarjei@nu.no',
            'role' => 'lead',
            'active' => 'yes',
        ),
    ),
    'dependencies' => array
    (
        'midcom' => array(),
        'midcom.helper.xsspreventer' => array(),
        'http_request' => array
        (
            'channel' => 'pear.php.net',
        ),
    ),
),
