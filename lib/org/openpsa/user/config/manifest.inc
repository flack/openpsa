'name' => 'org.openpsa.user',
'icon' => 'org.openpsa.user/user-management.png',
'purecode' => false,
'privileges' => array
(
    'access' => MIDCOM_PRIVILEGE_DENY,
    'manage' => MIDCOM_PRIVILEGE_DENY,
    'su' => MIDCOM_PRIVILEGE_DENY,
),
'class_mapping' => array(),
'version' => '9.0.0',
'state' => 'stable',
'package.xml' => array
(
    'summary' => 'OpenPSA User Management',
    'description' => 'User and Group management for OpenPSA',
    'maintainers' => array
    (
        'flack' => array
        (
            'name' => 'Andreas Flack',
            'email' => 'flack@contentcontrol-berlin.de',
            'role' => 'lead',
        ),
    ),
    'dependencies' => array
    (
        'org.openpsa.core' => array(),
        'org.openpsa.widgets' => array(),
        'org.openpsa.notifications' => array(),
        'midcom' => array(),
    ),
),
