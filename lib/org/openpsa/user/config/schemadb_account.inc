'default' => array
(
    'description'   => 'Account schema',
    'validation' => Array
    (
        Array
        (
            'callback' => array(new org_openpsa_user_validator, 'is_username_available'),
        ),
    ),

    'fields'  => array
    (
        'username' => array
        (
            'title'    => 'username',
            'storage'  => null,
            'type'     => 'text',
            'widget'   => 'text',
        ),

        'password' => array
        (
            'title' => 'password',
            'type' => 'text',
            'widget' => 'org_openpsa_user_widget_password',
            'storage' => null,
        ),

        'send_welcome_mail' => array
        (
            'title' => 'send_welcome_mail',
            'storage' => null,
            'type' => 'boolean',
            'widget' => 'checkbox',
        ),
    )
),