'default' => [
    'description' => 'account schema',
    'l10n_db' => 'midgard.admin.user',
    'validation' => [
        [
            'callback' => [new midgard_admin_user_validator, 'is_username_available'],
        ],
    ],
    'fields' => [
        'usertype' => [
            'title' => 'user type',
            'storage' => 'usertype',
            'type' => 'select',
            'widget' => 'select',
            'type_config' => [
                'options' => [
                    0 => 'none',
                    1 => 'user',
                    2 => 'admin'
                ]
             ]
        ],
        'username' => [
            // COMPONENT-REQUIRED
            'title' => 'username',
            'storage' => 'username',
            'type' => 'text',
            'widget' => 'text',
            'index_method' => 'noindex',
        ],
        'password' => [
            // COMPONENT-REQUIRED
            'title' => 'password',
            'storage' => null,
            'type' => 'text',
            'widget' => 'password',
            'index_method' => 'noindex',
            'widget_config' => [
                'require_password' => !midcom::get()->auth->admin
             ]
        ],
        'person' => [
            // NEEDED FOR VALIDATION
            'title'    => 'person',
            'storage'  => null,
            'type'     => 'text',
            'widget'   => 'hidden',
        ],
    ]
]