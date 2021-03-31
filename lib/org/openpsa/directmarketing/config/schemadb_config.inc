'config' => [
    'description' => 'default configuration schema',
    'fields' => [
        'token_size' => [
            'title' => 'recipient token length',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'org.openpsa.directmarketing',
                'name' => 'token_size',
            ],
            'type' => 'number',
            'widget' => 'text',
            'default' => 15,
            'start_fieldset' => [
                'title' => 'detectors and email configuration',
                'css_group' => 'area',
            ],
        ],
        'mail_send_backend' => [
            'title' => 'backend for sending email (backend specific configurations in org.openpsa.mail)',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'org.openpsa.directmarketing',
                'name' => 'mail_send_backend',
            ],
            'type' => 'select',
            'widget' => 'select',
            'type_config' => [
                'options' => [
                    'try_default' => 'system default',
                    'mail_smtp' => 'smtp',
                    'mail_sendmail' => 'sendmail',
                ],
            ],
        ],
        'bouncer_address' => [
            'title' => 'bounce detector address (use token to indicate place of the token)',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'org.openpsa.directmarketing',
                'name' => 'bouncer_address',
            ],
            'type' => 'text',
            'widget' => 'text',
            'start_fieldset' => [
                'title' => 'bounce detector configuration (note: requires special mail server configuration as well)',
                'css_group' => 'area',
            ],
            'end_fieldset' => '',
        ],
        'linkdetector_address' => [
            'title' => 'link detector base address (use token to indicate place of the token and url of link)',
            'storage' => [
                'location' => 'configuration',
                'domain' => 'org.openpsa.directmarketing',
                'name' => 'linkdetector_address',
            ],
            'type' => 'text',
            'widget' => 'text',
            'start_fieldset' => [
                'title' => 'link detector configuration',
                'css_group' => 'area',
            ],
            'end_fieldset' => 2,
        ],
    ],
],
