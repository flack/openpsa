'lostpassword_by_username' => [
    'description' => 'lost password by username',
    'validation' => [
    	[
            'callback' => [new org_openpsa_user_validator, 'username_exists'],
        ],
    ],
    'fields' => [
        'username' => [
            'title' => 'username',
            'storage' => 'username',
            'required' => true,
            'type' => 'text',
            'widget' => 'text',
        ],
    ],
],
'lostpassword_by_email' => [
    'description' => 'lost password by email',
    'validation' => [
    	[
            'callback' => [new org_openpsa_user_validator, 'email_exists'],
        ],
    ],
    'fields' => [
        'email' => [
            'title' => 'email',
            'storage' => 'email',
            'type' => 'text',
            'widget' => 'text',
            'validation' => 'email',
            'required' => true,
        ],
    ],
],
'lostpassword_by_email_username' => [
    'description' => 'lost password by email and username',
    'validation' => [
    	[
            'callback' => [new org_openpsa_user_validator, 'email_and_username_exist'],
        ],
    ],
    'fields' => [
        'username' => [
            'title' => 'username',
            'storage' => 'username',
            'required' => true,
            'type' => 'text',
            'widget' => 'text',
        ],
        'email' => [
            'title' => 'email',
            'storage' => 'email',
            'type' => 'text',
            'widget' => 'text',
            'validation' => 'email',
            'required' => true,
        ],
    ],
],