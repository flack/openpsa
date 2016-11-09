'default' => array
(
    'description'   => 'Account editing schema',
    'validation' => array
    (
        array
        (
            'callback' => array(new org_openpsa_user_validator, 'validate_edit_form'),
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

        'current_password' => array
        (
            'title' => 'current password',
            'type' => 'text',
            'widget' => 'text',
            'widget_config' => array
            (
                'hideinput' => true
            ),
            'storage' => null,
            'hidden' => (midcom::get()->auth->can_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface')),
            'required' => (!midcom::get()->auth->can_user_do('org.openpsa.user:manage', null, 'org_openpsa_user_interface')),
        ),

        'new_password' => array
        (
            'title' => 'new password',
            'type' => 'text',
            'widget' => 'password',
            'widget_config' => array
            (
                'require_password' => false
            ),

            'required' => false,
            'storage' => null,
        ),
    )
),