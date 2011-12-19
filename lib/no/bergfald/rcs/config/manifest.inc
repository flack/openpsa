'name' => 'no.bergfald.rcs',
'icon' => 'stock-icons/16x16/history.png',
'description' => 'Front and backend for a versioning library',
'purecode' => true,
'version' => '9.0beta2+git',
'state' => 'stable',
'privileges' => array(),
'class_mapping' => array(),
'package.xml' => array
(
    'license' => 'GPL',
    'maintainers' => array
    (
        'tarjei' => array
        (
            'name' => 'Tarjei Huse',
            'email' => 'tarjei@nu.no',
            'role' => 'lead',
        ),
        'bergie' => array
        (
            'name' => 'Henri Bergius',
            'email' => 'henri.bergius@iki.fi',
            'role' => 'developer',
        ),
        'adrenalin' => array
        (
            'name' => 'Arttu Manninen',
            'email' => 'arttu@kaktus.cc',
            'role' => 'developer',
        ),
    ),
    'dependencies' => array
    (
        'midcom.helper.xml' => array(),
        'Text_Diff' => array
        (
            'channel' => 'pear.php.net',
        ),
        'midcom' => array(),
    ),
),
