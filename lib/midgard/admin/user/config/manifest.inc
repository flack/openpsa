'name' => 'midgard.admin.user',
'description' => 'User management tool',
'icon' => 'midgard.admin.user/user-manager.png',
'purecode' => true,
'privileges' => array
(
    'access' => MIDCOM_PRIVILEGE_DENY,
),
'class_mapping' => array(),
'customdata' => array
(
    'asgard_plugin' => array
    (
        'class' => 'midgard_admin_user_plugin',
        'name' => 'User Management',
        'config' => '',
    ),
),
