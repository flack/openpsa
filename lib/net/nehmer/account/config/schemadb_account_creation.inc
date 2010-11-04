'account' => Array
(
    'description' => 'Standard Account',
    'fields' => Array
    (
        'firstname' => Array
        (
            'title' => 'firstname',
            'description' => '',
            'helptext' => '',

            'storage' => 'firstname',
            'required' => true,
            'type' => 'text',
            'type_config' => Array
            (
                'maxlength' => 50,
            ),
            'widget' => 'text',
        ),
        'lastname' => Array
        (
            'title' => 'lastname',
            'description' => '',
            'helptext' => '',

            'storage' => 'lastname',
            'required' => true,
            'type' => 'text',
            'type_config' => Array
            (
                'maxlength' => 50,
            ),
            'widget' => 'text',
        ),
        'username' => Array
        (
            'title' => 'username',
            'description' => '',
            'helptext' => '',

            'storage' => 'username',
            'required' => true,
            'type' => 'text',
            'type_config' => Array
            (
                'maxlength' => 50,
            ),
            'widget' => 'text',
        ),
        'email' => Array
        (
            'title' => 'email',
            'description' => '',
            'helptext' => '',

            'storage' => 'email',
            'type' => 'text',
            'type_config' => Array
            (
                'maxlength' => 100,
            ),
            'widget' => 'text',
            'validation' => 'email',
            'required' => true,
        ),
        'street' => Array
        (
            'title' => 'street',
            'description' => '',
            'helptext' => '',

            'storage' => 'street',
            'type' => 'text',
            'type_config' => Array
            (
                'maxlength' => 50,
            ),
            'widget' => 'text',
        ),
        'postcode' => Array
        (
            'title' => 'postcode',
            'description' => '',
            'helptext' => '',

            'storage' => 'postcode',
            'type' => 'text',
            'type_config' => Array
            (
                'maxlength' => 15,
            ),
            'widget' => 'text',
        ),
        'city' => Array
        (
            'title' => 'city',
            'description' => '',
            'helptext' => '',

            'storage' => 'city',
            'type' => 'text',
            'type_config' => Array
            (
                'maxlength' => 50,
            ),
            'widget' => 'text',
        ),
    ),
),

'simpleaccount' => Array
(
    'description' => 'Simple Account',
    'fields' => Array
    (
        'firstname' => Array
        (
            'title' => 'firstname',
            'description' => '',
            'helptext' => '',

            'storage' => 'firstname',
            'required' => true,
            'type' => 'text',
            'type_config' => Array
            (
                'maxlength' => 50,
            ),
            'widget' => 'text',
        ),
        'lastname' => Array
        (
            'title' => 'lastname',
            'description' => '',
            'helptext' => '',

            'storage' => 'lastname',
            'required' => true,
            'type' => 'text',
            'type_config' => Array
            (
                'maxlength' => 50,
            ),
            'widget' => 'text',
        ),
        'username' => Array
        (
            'title' => 'username',
            'description' => '',
            'helptext' => '',

            'storage' => 'username',
            'required' => true,
            'type' => 'text',
            'type_config' => Array
            (
                'maxlength' => 50,
            ),
            'widget' => 'text',

            'validation' => Array
            (
                Array
                (
                    'type' => 'regex',
                    'format' => '/^[^\+\*!]+$/',
                    'message' => 'invalid username',
                ),
            ),
        ),
        'email' => Array
        (
            'title' => 'email',
            'description' => '',
            'helptext' => '',

            'storage' => 'email',
            'type' => 'text',
            'type_config' => Array
            (
                'maxlength' => 100,
            ),
            'widget' => 'text',
            'validation' => 'email',
            'required' => true,
        ),
    ),
),