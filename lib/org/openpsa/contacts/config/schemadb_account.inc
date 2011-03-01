'default' => array
(
    'description'   => 'person',
    'l10n_db' => 'org.openpsa.contacts',
    'validation' => Array
    (
        Array
        (
            'callback' => 'check_username',
            'autoload_snippet' => 'file:/org/openpsa/contacts/config/validation_callbacks.inc',
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

            'start_fieldset' => array
            (
                'title' => 'account_fieldset',
                'css_group' => 'area meta',
            ),
        ),

        'password_dummy' => array
        (
            'title' => 'password_dummy',
            'type' => 'text',
            'widget' => 'text',
            'storage' => null,
        ),

        'send_welcome_mail' => array
        (
            'title' => 'send_welcome_mail',
            'storage' => null,
            'type' => 'boolean',
            'widget' => 'checkbox',

            'end_fieldset' => '',
        ),

    )
),