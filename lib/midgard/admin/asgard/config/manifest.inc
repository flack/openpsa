'name' => 'midgard.admin.asgard',
'icon' => 'midgard.admin.asgard/asgard2-16.png',
'purecode' => true,
'version' => '9.0.0+git',
'state' => 'stable',
'privileges' => array
(
    'access' => MIDCOM_PRIVILEGE_DENY,
    'manage_objects' => MIDCOM_PRIVILEGE_ALLOW,
),
'class_mapping' => array(),
'package.xml' => array
(
    'summary' => 'Next generation admin interface',
    'description' => 'Next generation admin interface',
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
            'email' => 'eero.afheurlin@iki.fi',
            'role' => 'lead',
        ),
        'adrenalin' => array
        (
            'name' => 'Arttu Manninen',
            'email' => 'arttu@kaktus.cc',
            'role' => 'lead',
        ),
        'solt' => array
        (
            'name' => 'Marcin Sołtysiak',
            'email' => 'olga@olga.pl',
            'role' => 'developer',
        ),
        'w_i' => array
        (
            'name' => 'Jerry Jalava',
            'email' => 'jerry.jalava@gmail.com',
            'role' => 'developer',
            'active' => 'no',
        ),
    ),
    'dependencies' => array
    (
        'midcom.helper.reflector' => array(),
        'midcom.helper.activitystream' => array
        (
            'optional' => 'yes'
        ),
        'midcom.helper.datamanager2' => array(),
        'midcom.admin.help' => array(),
    ),
),
