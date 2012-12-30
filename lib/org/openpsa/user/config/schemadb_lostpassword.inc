'lostpassword_by_username' => array
(
    'description' => 'lost password by username',
    'validation' => Array
    (
        Array
        (
            'callback' => array(new org_openpsa_user_validator, 'username_exists'),
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
            'callback' => array(new org_openpsa_user_validator, 'email_exists'),
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
            'callback' => array(new org_openpsa_user_validator, 'email_and_username_exist'),
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