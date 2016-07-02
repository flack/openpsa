'name' => 'midcom.admin.folder',
'icon' => 'stock-icons/16x16/folder.png',
'purecode' => true,
'version' => '9.1.0-dev',
'state' => 'stable',
'privileges' => array
(
    'topic_management' => MIDCOM_PRIVILEGE_DENY,
    'template_management' => MIDCOM_PRIVILEGE_DENY,
    'symlinks' => MIDCOM_PRIVILEGE_DENY,
),
'package.xml' => array
(
    'summary' => 'Folder management',
    'description' => 'Management of MidCOM site folders',
    'maintainers' => array
    (
        'bergie' => array
        (
            'name' => 'Henri Bergius',
            'email' => 'henri.bergius@iki.fi',
            'role' => 'lead',
        ),
        'adrenalin' => array
        (
            'name' => 'Arttu Manninen',
            'email' => 'arttu@kaktus.cc',
            'role' => 'lead',
        ),
        'torben' => array
        (
            'name' => 'Torben Nehmer',
            'email' => 'torben@nehmer.net',
            'role' => 'developer',
            'active' => 'no',
        ),
    ),
    'dependencies' => array
    (
        'midcom.helper.datamanager2' => array(),
        'midcom' => array(),
    ),
),
