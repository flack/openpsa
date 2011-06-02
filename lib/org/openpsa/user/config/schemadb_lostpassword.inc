'lostpassword_by_username' => array
(
    'description' => 'lost password by username',
    'validation' => Array
    (
        Array
        (
            'callback' => 'username_exists',
            'autoload_snippet' => 'file:/org/openpsa/user/config/validation_callbacks.inc',
        ),
    ),
    'fields' => array
    (
        'username' => array
        (
            'title' => 'username',
            'storage' => 'username',
            'required' => true,
            'type' => 'text',
            'widget' => 'text',
        ),
    ),
),
'lostpassword_by_email' => array
(
    'description' => 'lost password by email',
    'validation' => Array
    (
        Array
        (
            'callback' => 'email_exists',
            'autoload_snippet' => 'file:/org/openpsa/user/config/validation_callbacks.inc',
        ),
    ),
    'fields' => array
    (
        'email' => array
        (
            'title' => 'email',
            'storage' => 'email',
            'type' => 'text',
            'widget' => 'text',
            'validation' => 'email',
            'required' => true,
        ),
    ),
),
'lostpassword_by_email_username' => array
(
    'description' => 'lost password by email and username',
    'validation' => Array
    (
        Array
        (
            'callback' => 'email_and_username_exist',
            'autoload_snippet' => 'file:/org/openpsa/user/config/validation_callbacks.inc',
        ),
    ),
    'fields' => array
    (
        'username' => array
        (
            'title' => 'username',
            'storage' => 'username',
            'required' => true,
            'type' => 'text',
            'widget' => 'text',
        ),
        'email' => array
        (
            'title' => 'email',
            'storage' => 'email',
            'type' => 'text',
            'widget' => 'text',
            'validation' => 'email',
            'required' => true,
        ),
    ),
),