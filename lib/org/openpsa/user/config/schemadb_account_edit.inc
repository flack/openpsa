'default' => [
    'description'   => 'Account editing schema',
    'validation' => [
    	[
            'callback' => [new org_openpsa_user_validator, 'validate_edit_form'],
        ]
    ],

    'fields'  => [
        'username' => [
            'title'    => 'username',
            'storage'  => 'username',
            'type'     => 'text',
            'widget'   => 'text',
            'required' => 'true',
        ],

        'current_password' => [
            'title' => 'current password',
            'type' => 'text',
            'widget' => 'text',
            'widget_config' => [
                'hideinput' => true
            ],
            'storage' => null,
            'hidden' => (midcom::get()->auth->can_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface')),
            'required' => (!midcom::get()->auth->can_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface')),
        ],

        'new_password' => [
            'title' => 'new password',
            'type' => 'text',
            'widget' => 'password',
            'widget_config' => [
                'require_password' => false
            ],

            'required' => false,
            'storage' => null,
        ],
        // needed for validation
        'person' => [
            'title'    => 'person',
            'storage'  => null,
            'type'     => 'text',
            'widget'   => 'hidden',
        ],
    ]
],