'name' => 'org.openpsa.mypage',
'purecode' => false,
'privileges' => array(),
'class_definitions' => array(),
'version' => '8.09.9',
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
        'org.openpsa.contactwidget' => array(),
        'Calendar' => array
        (
            'channel' => 'pear.php.net',
        ),
        'org.routamc.positioning' => array(),
        'midcom' => array(),
    ),
),
