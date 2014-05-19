'default' => array
(
    'description' => 'account schema',
    'validation' => array
    (
        array
        (
            'callback' => array(new midcom_admin_user_validator, 'is_username_available'),
        ),
    ),
    'fields' => array
    (
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
        ),
        'person' => array
        (
            'title'    => 'person',
            'storage'  => null,
            'type'     => 'text',
            'widget'   => 'hidden',
        ),
    )
)