'name' => 'org.openpsa.core',
'icon' => 'org.openpsa.core/openpsa-16x16.png',
'purecode' => true,
'privileges' => array(),
'class_mapping' => array(),
'version' => '9.1.0-dev',
'state' => 'stable',
'package.xml' => array
(
    'summary' => 'OpenPSA core library',
    'description' => 'OpenPSA core library',
    'maintainers' => array
    (
        'rambo' => array
        (
            'name' => 'Eero af Heurlin',
            'email' => 'eero.afheurlin@iki.fi',
            'role' => 'lead',
        ),
        'flack' => array
        (
            'name' => 'Andreas Flack',
            'email' => 'flack@contentcontrol-berlin.de',
            'role' => 'developer',
        ),
        'bergie' => array
        (
            'name' => 'Henri Bergius',
            'email' => 'henri.bergius@iki.fi',
            'role' => 'lead',
            'active' => 'no',
        ),
    ),
    'dependencies' => array
    (
        'org.openpsa.helpers' => array(),
        'midcom' => array(),
    ),
),
