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
            'customdata' => Array
            (
                'visible_mode' => 'always',
            ),
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
            'customdata' => Array
            (
                'visible_mode' => 'always',
            ),
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
            'customdata' => Array
            (
                'visible_mode' => 'link',
                'visible_link' => 'city',
            ),
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
        'birthdate' => Array
        (
            'title' => 'birthdate',
            'description' => '',
            'helptext' => '',

            'storage' => 'birthdate',
            'type' => 'date',
            'widget' => 'simpledate',
            'widget_config' => Array
            (
                'format' => 'dmY',
                'minyear' => 1900,
                'maxyear' => 2010,
            ),
            'customdata' => Array
            (
                'visible_mode' => 'never',
            ),
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
            'customdata' => Array
            (
                'visible_mode' => 'always',
            ),
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
            'customdata' => Array
            (
                'visible_mode' => 'always',
            ),
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
            'customdata' => Array
            (
                'visible_mode' => 'always',
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
        ),
        'birthdate' => Array
        (
            'title' => 'birthdate',
            'description' => '',
            'helptext' => '',

            'storage' => 'birthdate',
            'type' => 'date',
            'widget' => 'simpledate',
            'widget_config' => Array
            (
                'format' => 'dmY',
                'minyear' => 1900,
                'maxyear' => 2010,
            ),
            'customdata' => Array
            (
                'visible_mode' => 'never',
            ),
        ),
    ),
),