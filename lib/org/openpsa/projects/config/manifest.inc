'name' => 'org.openpsa.projects',
'purecode' => false,
'privileges' => array(),
'class_definitions' => array
(
    'midcom_dba_classes.inc'
),
'version' => '8.09.9',
'state' => 'stable',
'package.xml' => array
(
    'summary' => 'Project/Task management and hour reporting',
    'description' => 'Manage tasks and projects, report hours towards them.',
    'maintainers' => array
    (
        'bergie' => array
        (
            'name' => 'Henri Bergius',
            'email' => 'henri.bergius@iki.fi',
            'role' => 'lead',
            'active' => 'no',
        ),
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
    ),
    'dependencies' => array
    (
        'org.openpsa.core' => array(),
        'org.openpsa.helpers' => array(),
        'midcom.helper.datamanager2' => array(),
        'org.openpsa.contactwidget' => array(),
        'midcom' => array(),
    ),
),

