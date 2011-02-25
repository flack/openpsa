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
            'required' => 'true',

            'start_fieldset' => array
            (
                'title' => 'account_fieldset',
                'css_group' => 'area meta',
            ),
        ),

        'old_password' => array
        (
            'title' => 'old password',
            'type' => 'text',
            'widget' => 'text',
            'storage' => null,
            'hidden' => ($_MIDCOM->auth->admin),
        ),

        'new_password' => array
        (
            'title' => 'new password',
            'type' => 'text',
            'widget' => 'password',

            'required' => false,

            'storage' => null,

            'widget_config' => array('require_password'=>false),
        ),

    )
),