'name' => 'midcom.admin.babel',
'icon' => 'stock-icons/16x16/locale.png',
'description' => 'MidCOM localization utility',
'purecode' => true,
'version' => '9.0pre',
'privileges' => array
(
    'access' => MIDCOM_PRIVILEGE_ALLOW,
),
'class_mapping' => array(),
'package.xml' => array
(
    'license' => 'GPL',
    'maintainers' => array
    (
        'solt' => array
        (
            'name' => 'Marcin Sołtysiak',
            'email' => 'olga@olga.pl',
            'role' => 'lead',
        ),
        'adrenalin' => array
        (
            'name' => 'Arttu Manninen',
            'role' => 'lead',
        ),
    ),
    'dependencies' => array
    (
        'midcom' => array(),
    ),
),
'state' => 'stable',
'customdata' => array
(
    'asgard_plugin' => array
    (
        'class' => 'midcom_admin_babel_plugin',
        'src' => 'file:/midcom/admin/babel/plugin.php',
        'name' => 'midcom.admin.babel',
        'config' => '',
    ),
),
