'default' => array
(
    'description'   => 'Account schema',
    'validation' => Array
    (
        Array
        (
            'callback' => 'is_username_available',
            'autoload_snippet' => 'file:/org/openpsa/user/config/validation_callbacks.inc',
        ),
    ),

    'fields'  => array
    (
        'username' => array
        (
            'title'    => 'username',
            'storage'  => 'username',
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