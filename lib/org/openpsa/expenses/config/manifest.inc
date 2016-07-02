'name' => 'org.openpsa.expenses',
'purecode' => false,
'version' => '9.0.0',
'state' => 'stable',
'privileges' => array(),
'class_mapping' => array(),
'package.xml' => array
(
    'summary' => 'Expense tracking',
    'description' => 'Tracking of work hours, expenses and mileages',
    'maintainers' => array
    (
        'bergie' => array
        (
            'name' => 'Henri Bergius',
            'email' => 'henri.bergius@iki.fi',
            'role' => 'lead',
            'active' => 'no',
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
        'org.openpsa.projects' => array(),
        'org.openpsa.core' => array(),
        'org.openpsa.helpers' => array(),
        'midcom.helper.datamanager2' => array(),
        'midcom' => array(),
    ),
),
