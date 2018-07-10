'name' => 'midgard.admin.user',
'description' => 'User management tool',
'icon' => 'users',
'purecode' => true,
'privileges' => [
    'access' => MIDCOM_PRIVILEGE_DENY,
],
'class_mapping' => [],
'customdata' => [
    'asgard_plugin' => [
        'class' => 'midgard_admin_user_plugin',
        'name' => 'User Management',
        'config' => '',
    ],
],
