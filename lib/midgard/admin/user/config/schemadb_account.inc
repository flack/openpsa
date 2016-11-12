'default' => array
(
    'description' => 'account schema',
    'l10n_db' => 'midgard.admin.user',
    'validation' => array
    (
        array
        (
            'callback' => array(new midgard_admin_user_validator, 'is_username_available'),
        ),
    ),
    'fields' => array
    (
        'usertype' => array
        (
            'title' => 'user type',
            'storage' => 'usertype',
            'type' => 'select',
            'widget' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    0 => 'none',
                    1 => 'user',
                    2 => 'admin'
                )
            ),
            'hidden' => (extension_loaded('midgard'))
        ),
        'username' => array
        (
            // COMPONENT-REQUIRED
            'title' => 'username',
            'storage' => 'username',
            'type' => 'text',
            'widget' => 'text',
            'index_method' => 'noindex',
        ),
        'password' => array
        (
            // COMPONENT-REQUIRED
            'title' => 'password',
            'storage' => null,
            'type' => 'text',
            'widget' => 'password',
            'index_method' => 'noindex',
            'widget_config' => array
            (
                'require_password' => !midcom::get()->auth->admin
            )
        ),
        'person' => array
        (
            // NEEDED FOR VALIDATION
            'title'    => 'person',
            'storage'  => null,
            'type'     => 'text',
            'widget'   => 'hidden',
        ),
    )
)