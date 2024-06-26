<?php
return [
    'default' => [
        'description'   => 'Account schema',
        'validation' => [
        	[
                'callback' => [new org_openpsa_user_validator, 'validate_create_form'],
            ],
        ],

        'fields'  => [
            'username' => [
                'title'    => 'username',
                'storage'  => null,
                'type'     => 'text',
                'widget'   => 'text',
            ],

            'password' => [
                'title' => 'password',
                'type' => 'text',
                'widget' => 'org_openpsa_user_widget_password',
                'storage' => null,
            ],

            'send_welcome_mail' => [
                'title' => 'send_welcome_mail',
                'storage' => null,
                'type' => 'boolean',
                'widget' => 'checkbox',
            ],
        ]
    ]
];