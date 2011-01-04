'name' => 'org.openpsa.httplib',
'icon' => 'org.openpsa.httplib/httplib.png',
'purecode' => true,
'version' => '8.09.9',
'state' => 'stable',
'privileges' => array(),
'class_mapping' => array(),
'package.xml' => array
(
    'summary' => 'HTTP client library',
    'description' => 'HTTP client library',
    'maintainers' => array
    (
        'bergie' => array
        (
            'name' => 'Henri Bergius',
            'email' => 'henri.bergius@iki.fi',
            'role' => 'lead',
        ),
        'rambo' => array
        (
            'name' => 'Eero af Heurlin',
            'email' => 'eero.afheurlin@nemein.com',
            'role' => 'lead',
        ),
    ),
    'dependencies' => array
    (
        'HTTP_request' => array
        (
            'channel' => 'pear.php.net',
        ),
        'Console_Getargs' => array
        (
            'channel' => 'pear.php.net',
        ),
        'midcom' => array(),
    ),
),
