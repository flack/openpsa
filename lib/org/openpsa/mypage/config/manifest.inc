'name' => 'org.openpsa.mypage',
'purecode' => false,
'privileges' => array(),
'class_mapping' => array(),
'version' => '9.1.0-dev',
'state' => 'stable',
'package.xml' => array
(
    'summary' => 'OpenPSA summary page',
    'description' => 'At a glance view of your documents, tasks etc',
    'maintainers' => array
    (
        'flack' => array
        (
            'name' => 'Andreas Flack',
            'email' => 'flack@contentcontrol-berlin.de',
            'role' => 'lead',
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
        'org.openpsa.core' => array(),
        'org.openpsa.helpers' => array(),
        'midcom.helper.datamanager2' => array(),
        'org.openpsa.projects' => array(),
        'org.openpsa.widgets' => array(),
        'org.routamc.positioning' => array(),
    ),
),
