'config' => array
(
    'description' => 'default configuration schema',
    'fields' => array
    (
        'token_size' => array
        (
            'title' => 'recipient token length',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.directmarketing',
                'name' => 'token_size',
            ),
            'type' => 'number',
            'widget' => 'text',
            'default' => 15,
            'start_fieldset' => array
            (
                'title' => 'detectors and email configuration',
                'css_group' => 'area',
            ),
        ),
        'mail_send_backend' => array
        (
            'title' => 'backend for sending email (backend specific configurations in org.openpsa.mail)',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.directmarketing',
                'name' => 'mail_send_backend',
            ),
            'type' => 'select',
            'widget' => 'select',
            'type_config' => array
            (
                'options' => array
                (
                    'try_default' => 'system default',
                    'bouncer' => 'any bounce detector compatible',
                    'mail_smtp' => 'smtp',
                    'mail_sendmail' => 'sendmail',
                    'mail' => 'mail() function (not recommended)',
                ),
            ),
        ),
        'bouncer_address' => array
        (
            'title' => 'bounce detector address (use token to indicate place of the token)',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.directmarketing',
                'name' => 'bouncer_address',
            ),
            'type' => 'text',
            'widget' => 'text',
            'start_fieldset' => array
            (
                'title' => 'bounce detector configuration (note: requires special mail server configuration as well)',
                'css_group' => 'area',
            ),
            'end_fieldset' => '',
        ),
        'linkdetector_address' => array
        (
            'title' => 'link detector base address (use token to indicate place of the token and url of link)',
            'storage' => array
            (
                'location' => 'configuration',
                'domain' => 'org.openpsa.directmarketing',
                'name' => 'linkdetector_address',
            ),
            'type' => 'text',
            'widget' => 'text',
            'start_fieldset' => array
            (
                'title' => 'link detector configuration',
                'css_group' => 'area',
            ),
            'end_fieldset' => 2,
        ),
    ),
),
